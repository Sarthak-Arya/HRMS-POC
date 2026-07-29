<?php

namespace App\Services\Payroll;

use App\Enums\Compensation\ComponentType;
use App\Enums\Compensation\StatutoryComponent;
use App\Enums\Payroll\PayrollLineComponentType;
use App\Models\CompensationComponent;
use App\Models\EmployeePayroll;
use App\Models\PayrollRun;
use App\Services\Compensation\CompensationResolver;
use App\Services\Compensation\StatutoryComplianceCalculator;
use App\Services\Settings\Adapters\StatutorySettingsAdapter;
use App\Support\Payroll\SalarySheetStatutoryRates;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SalarySheetService
{
    public function __construct(
        private readonly CompensationResolver $compensationResolver,
        private readonly SalarySheetStatutoryRates $statutoryRates,
        private readonly StatutoryComplianceCalculator $statutoryCalculator,
        private readonly StatutorySettingsAdapter $statutorySettings,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function buildSheets(PayrollRun $run, string $groupBy = 'company'): array
    {
        $run->loadMissing('company');
        $earningComponents = $this->earningComponents($run->company_id);
        $payrolls = EmployeePayroll::query()
            ->where('payroll_run_id', $run->id)
            ->with([
                'employee.department',
                'employee.location',
                'lines.component',
                'attendanceSummary',
            ])
            ->get()
            ->sortBy(fn (EmployeePayroll $payroll) => $payroll->employee?->employee_code ?? '')
            ->values();

        $employeeRows = $payrolls
            ->map(fn (EmployeePayroll $payroll) => $this->buildEmployeeRow($run, $payroll, $earningComponents))
            ->filter()
            ->values();

        $grouped = match ($groupBy) {
            'department' => $employeeRows->groupBy(fn (array $row) => (string) ($row['department_id'] ?? 'unassigned')),
            'location' => $employeeRows->groupBy(fn (array $row) => (string) ($row['location_id'] ?? 'unassigned')),
            default => collect(['company' => $employeeRows]),
        };

        return $grouped
            ->map(fn (Collection $rows, string $groupKey) => $this->buildSheet(
                $run,
                $rows->values()->all(),
                $earningComponents,
                $groupBy,
                $groupKey,
            ))
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $employeeRows
     * @param  Collection<int, CompensationComponent>  $earningComponents
     * @return array<string, mixed>
     */
    private function buildSheet(
        PayrollRun $run,
        array $employeeRows,
        Collection $earningComponents,
        string $groupBy,
        string $groupKey,
    ): array {
        $company = $run->company;
        $rates = $this->statutoryRates->forCompany($company);
        $profile = app(\App\Services\Settings\CompanySettingsService::class)
            ->getSection($company->id, \App\Enums\Settings\CompanySettingsSection::CompanyProfile);
        $period = Carbon::create($run->year, $run->month);

        $totals = $this->emptyTotals($earningComponents);
        foreach ($employeeRows as $index => $row) {
            $employeeRows[$index]['sno'] = $index + 1;
            $totals = $this->accumulateTotals($totals, $row, $earningComponents);
        }

        $statutorySummary = $this->buildStatutorySummary($employeeRows, $rates);

        return [
            'slug' => Str::slug($this->groupLabel($run, $employeeRows, $groupBy, $groupKey)),
            'title' => $this->groupLabel($run, $employeeRows, $groupBy, $groupKey),
            'group_by' => $groupBy,
            'company' => $company,
            'period_label' => strtoupper($period->format('F Y')),
            'period_month' => $run->month,
            'period_year' => $run->year,
            'esi_code' => $profile['esiCode'] ?? $company->esi_code ?? null,
            'pf_code' => $profile['pfCode'] ?? $company->pf_code ?? null,
            'earning_columns' => $earningComponents->pluck('component_name', 'id')->all(),
            'employees' => $employeeRows,
            'totals' => $totals,
            'statutory_summary' => $statutorySummary,
        ];
    }

    /**
     * @param  Collection<int, CompensationComponent>  $earningComponents
     * @return array<string, mixed>|null
     */
    private function buildEmployeeRow(
        PayrollRun $run,
        EmployeePayroll $payroll,
        Collection $earningComponents,
    ): ?array {
        $employee = $payroll->employee;
        if ($employee === null) {
            return null;
        }

        $asOf = Carbon::create($run->year, $run->month)->endOfMonth();
        $resolved = $this->compensationResolver->resolveForEmployee($employee, $asOf);
        $attendance = $payroll->attendanceSummary;

        $actualEarnings = [];
        foreach ($resolved->lines as $line) {
            if ($line->componentType !== ComponentType::EARNING) {
                continue;
            }

            $actualEarnings[$line->componentId] = round((float) ($line->monthlyAmount ?? 0), 2);
        }

        $payableEarnings = [];
        $variablePay = 0.0;
        foreach ($payroll->lines->where('component_type', PayrollLineComponentType::EARNING) as $line) {
            $componentId = $line->component_id;
            $amount = round((float) $line->calculated_amount, 2);

            if ($componentId !== null && $earningComponents->has($componentId)) {
                $payableEarnings[$componentId] = ($payableEarnings[$componentId] ?? 0) + $amount;
            } else {
                $variablePay += $amount;
            }
        }

        $actualGross = round(array_sum($actualEarnings), 2);
        $payableGross = round(array_sum($payableEarnings) + $variablePay, 2);

        $deductions = $this->mapDeductions($payroll);
        $employerContributions = $this->mapEmployerContributions($payroll, $rates = $this->statutoryRates->forCompany($run->company));
        $statutoryConfig = $this->statutorySettings->read((int) $run->company_id);

        $payablePfWages = $this->amountForFlaggedComponents($payableEarnings, $earningComponents, 'included_in_pf_wages');
        $payableEsiWages = $this->amountForFlaggedComponents($payableEarnings, $earningComponents, 'included_in_esi_wages');

        $workedDays = (float) ($attendance?->worked_days ?? 0);
        $totalDays = (float) ($attendance?->total_days ?? 0);

        $epfCalc = $this->statutoryCalculator->calculateEpf($payablePfWages, array_merge(
            (array) ($statutoryConfig['epf'] ?? []),
            [
                'wageCeiling' => $rates['pf_wage_ceiling'],
                'workedDays' => $workedDays > 0 ? $workedDays : 30,
                'totalDays' => $totalDays > 0 ? $totalDays : 30,
            ],
        ));
        $esiCalc = $this->statutoryCalculator->calculateEsi($payableEsiWages, array_merge(
            (array) ($statutoryConfig['esi'] ?? []),
            [
                'wageCeiling' => $rates['esi_wage_ceiling'],
                'employeeContributionPercent' => $rates['esi_employee_percent'],
                'employerContributionPercent' => $rates['esi_employer_percent'],
                'forceCovered' => filled($employee->esi_no),
                'daysInMonth' => $totalDays > 0 ? $totalDays : 30,
            ],
        ));

        $pfWages = (float) $epfCalc['restricted_pf_wage'];
        $esiWages = $esiCalc['eligible'] ? (float) $esiCalc['gross_wage'] : 0.0;
        $esiWages = min($esiWages, (float) $rates['esi_wage_ceiling']);

        if ($deductions['pf'] <= 0 && filled($employee->pf_no) && ($statutoryConfig['epf']['enabled'] ?? false)) {
            $deductions['pf'] = (float) $epfCalc['employee_epf'];
        }
        if ($deductions['esi'] <= 0 && filled($employee->esi_no) && ($statutoryConfig['esi']['enabled'] ?? false) && $esiCalc['eligible']) {
            $deductions['esi'] = (float) $esiCalc['employee_esi'];
        }
        if ($employerContributions['esi'] <= 0 && $esiCalc['eligible'] && filled($employee->esi_no)) {
            $employerContributions['esi'] = (float) $esiCalc['employer_esi'];
        }
        if ($employerContributions['pf'] <= 0 && filled($employee->pf_no) && ($statutoryConfig['epf']['enabled'] ?? false)) {
            $employerContributions['pf'] = (float) $epfCalc['employer_total_12'];
        }

        return [
            'employee_id' => $employee->id,
            'department_id' => $employee->department_id,
            'department_name' => $employee->department?->department_name,
            'location_id' => $employee->location_id,
            'location_name' => $employee->location?->name,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->employee_name,
            'father_name' => $employee->father_name,
            'esi_no' => $employee->esi_no,
            'pf_no' => $employee->pf_no,
            'work_days' => $workedDays,
            'holiday_days' => (float) ($attendance?->holiday_days ?? 0),
            'total_days' => $totalDays,
            'actual_earnings' => $actualEarnings,
            'actual_gross' => $actualGross,
            'payable_earnings' => $payableEarnings,
            'variable_pay' => round($variablePay, 2),
            'payable_gross' => $payableGross,
            'esi_wages' => round($esiWages, 2),
            'esi_employer' => $employerContributions['esi'],
            'pf_wages' => round($pfWages, 2),
            'pf_employee' => $deductions['pf'],
            'pf_employer' => $employerContributions['pf'],
            'pf_edli' => (float) $epfCalc['edli'],
            'pf_admin' => (float) $epfCalc['admin_charges'],
            'tds' => $deductions['tds'],
            'advance' => $deductions['advance'],
            'other_deductions' => $deductions['other'],
            'total_deductions' => round(
                $deductions['pf'] + $deductions['esi'] + $deductions['tds'] + $deductions['advance'] + $deductions['other'],
                2,
            ),
            'net_pay' => round((float) $payroll->net_pay, 2),
            'esi_employee' => $deductions['esi'],
        ];
    }

    /**
     * @return Collection<int, CompensationComponent>
     */
    private function earningComponents(int $companyId): Collection
    {
        return CompensationComponent::query()
            ->where('company_id', $companyId)
            ->where('component_type', ComponentType::EARNING)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('component_name')
            ->get()
            ->keyBy('id');
    }

    /**
     * @return array{pf: float, esi: float, tds: float, advance: float, other: float}
     */
    private function mapDeductions(EmployeePayroll $payroll): array
    {
        $mapped = ['pf' => 0.0, 'esi' => 0.0, 'tds' => 0.0, 'advance' => 0.0, 'other' => 0.0];

        foreach ($payroll->lines->whereIn('component_type', [PayrollLineComponentType::DEDUCTION, PayrollLineComponentType::BENEFIT]) as $line) {
            $amount = round((float) $line->calculated_amount, 2);
            $statutory = $line->component?->statutory_component;
            $name = Str::lower($line->component_name);

            if ($statutory === StatutoryComponent::PF) {
                $mapped['pf'] += $amount;
            } elseif ($statutory === StatutoryComponent::ESIC) {
                $mapped['esi'] += $amount;
            } elseif ($statutory === StatutoryComponent::TDS) {
                $mapped['tds'] += $amount;
            } elseif (str_contains($name, 'advance')) {
                $mapped['advance'] += $amount;
            } else {
                $mapped['other'] += $amount;
            }
        }

        return $mapped;
    }

    /**
     * @return array{esi: float, pf: float}
     */
    private function mapEmployerContributions(EmployeePayroll $payroll, array $rates): array
    {
        $mapped = ['esi' => 0.0, 'pf' => 0.0];

        foreach ($payroll->lines->where('component_type', PayrollLineComponentType::EMPLOYER_CONTRIBUTION) as $line) {
            $amount = round((float) $line->calculated_amount, 2);
            $statutory = $line->component?->statutory_component;

            if ($statutory === StatutoryComponent::ESIC) {
                $mapped['esi'] += $amount;
            } elseif ($statutory === StatutoryComponent::PF) {
                $mapped['pf'] += $amount;
            }
        }

        return $mapped;
    }

    /**
     * @param  array<int, float>  $amounts
     * @param  Collection<int, CompensationComponent>  $earningComponents
     */
    private function amountForFlaggedComponents(array $amounts, Collection $earningComponents, string $flag): float
    {
        $total = 0.0;

        foreach ($earningComponents as $componentId => $component) {
            if (! ($component->{$flag} ?? false)) {
                continue;
            }

            $total += (float) ($amounts[$componentId] ?? 0);
        }

        return round($total, 2);
    }

    /**
     * @param  list<array<string, mixed>>  $employeeRows
     */
    private function groupLabel(PayrollRun $run, array $employeeRows, string $groupBy, string $groupKey): string
    {
        if ($groupBy === 'company' || $groupKey === 'company') {
            return $run->company?->company_name ?? 'Company';
        }

        $first = $employeeRows[0] ?? [];

        return match ($groupBy) {
            'department' => $first['department_name'] ?? 'Unassigned Department',
            'location' => $first['location_name'] ?? 'Unassigned Location',
            default => $run->company?->company_name ?? 'Company',
        };
    }

    /**
     * @param  Collection<int, CompensationComponent>  $earningComponents
     * @return array<string, mixed>
     */
    private function emptyTotals(Collection $earningComponents): array
    {
        return [
            'actual_earnings' => array_fill_keys($earningComponents->keys()->all(), 0.0),
            'payable_earnings' => array_fill_keys($earningComponents->keys()->all(), 0.0),
            'actual_gross' => 0.0,
            'variable_pay' => 0.0,
            'payable_gross' => 0.0,
            'esi_wages' => 0.0,
            'esi_employer' => 0.0,
            'pf_wages' => 0.0,
            'pf_employee' => 0.0,
            'esi_employee' => 0.0,
            'tds' => 0.0,
            'advance' => 0.0,
            'other_deductions' => 0.0,
            'total_deductions' => 0.0,
            'net_pay' => 0.0,
        ];
    }

    /**
     * @param  Collection<int, CompensationComponent>  $earningComponents
     * @param  array<string, mixed>  $totals
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function accumulateTotals(array $totals, array $row, Collection $earningComponents): array
    {
        foreach ($earningComponents as $componentId => $component) {
            $totals['actual_earnings'][$componentId] += (float) ($row['actual_earnings'][$componentId] ?? 0);
            $totals['payable_earnings'][$componentId] += (float) ($row['payable_earnings'][$componentId] ?? 0);
        }

        foreach (['actual_gross', 'variable_pay', 'payable_gross', 'esi_wages', 'esi_employer', 'pf_wages', 'pf_employee', 'esi_employee', 'tds', 'advance', 'other_deductions', 'total_deductions', 'net_pay'] as $key) {
            $totals[$key] += (float) ($row[$key] ?? 0);
        }

        return $totals;
    }

    /**
     * @param  list<array<string, mixed>>  $employeeRows
     * @param  array<string, float>  $rates
     * @return array<string, mixed>
     */
    private function buildStatutorySummary(array $employeeRows, array $rates): array
    {
        $esiEmployees = collect($employeeRows)->filter(fn (array $row) => ! empty($row['esi_no']) && (float) $row['esi_wages'] > 0);
        $pfEmployees = collect($employeeRows)->filter(fn (array $row) => ! empty($row['pf_no']) && (float) $row['pf_wages'] > 0);

        $totalGross = round(collect($employeeRows)->sum('payable_gross'), 2);
        $exemptedEsi = round(collect($employeeRows)->filter(fn (array $row) => empty($row['esi_no']))->sum('payable_gross'), 2);
        $exemptedPf = round(collect($employeeRows)->filter(fn (array $row) => empty($row['pf_no']))->sum('payable_gross'), 2);

        $esiWages = round($esiEmployees->sum('esi_wages'), 2);
        $esiEmployeeContribution = round($esiEmployees->sum('esi_employee'), 2);
        $esiEmployerContribution = round($esiEmployees->sum('esi_employer'), 2);

        $pfWages = round($pfEmployees->sum('pf_wages'), 2);
        $pfEmployeeContribution = round($pfEmployees->sum('pf_employee'), 2);
        $pfEmployerContribution = round($pfEmployees->sum(fn (array $row) => (float) ($row['pf_employer'] ?? (
            min((float) $row['pf_wages'], $rates['pf_wage_ceiling']) * $rates['pf_employer_percent'] / 100
        ))), 2);

        $chalan01 = round($pfEmployeeContribution + $pfEmployerContribution, 2);
        $chalan10 = $this->statutoryCalculator->applyEstablishmentAdminMinimum(
            round($pfEmployees->sum(fn (array $row) => (float) ($row['pf_admin'] ?? ((float) $row['pf_wages'] * $rates['pf_admin_percent'] / 100))), 2),
        );
        $chalan21 = round($pfWages * $rates['pf_inspection_percent'] / 100, 2);
        $chalan22 = 0.0;
        $chalan02 = round($pfEmployees->sum(fn (array $row) => (float) ($row['pf_edli'] ?? ((float) $row['pf_wages'] * $rates['pf_edli_percent'] / 100))), 2);
        $totalPf = round($chalan01 + $chalan10 + $chalan21 + $chalan22 + $chalan02, 2);

        return [
            'employee_count' => count($employeeRows),
            'total_gross_salary' => $totalGross,
            'exempted_esi_salary' => $exemptedEsi,
            'exempted_pf_salary' => $exemptedPf,
            'esi_employee_count' => $esiEmployees->count(),
            'esi_wages' => $esiWages,
            'esi_employee_contribution' => $esiEmployeeContribution,
            'esi_employer_contribution' => $esiEmployerContribution,
            'esi_total' => round($esiEmployeeContribution + $esiEmployerContribution, 2),
            'pf_employee_count' => $pfEmployees->count(),
            'pf_wages' => $pfWages,
            'pf_chalan_01' => $chalan01,
            'pf_chalan_10' => $chalan10,
            'pf_chalan_21' => $chalan21,
            'pf_chalan_22' => $chalan22,
            'pf_chalan_02' => $chalan02,
            'pf_total' => $totalPf,
        ];
    }
}
