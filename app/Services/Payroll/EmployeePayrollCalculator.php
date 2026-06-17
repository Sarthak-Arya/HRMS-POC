<?php

namespace App\Services\Payroll;

use App\Enums\Compensation\ComponentType;
use App\Enums\Payroll\EmployeeLoanStatus;
use App\Enums\Payroll\PayrollAdjustmentType;
use App\Enums\Payroll\PayrollLineComponentType;
use App\Models\Employee;
use App\Models\EmployeeCompensationHistory;
use App\Models\EmployeeLoan;
use App\Models\MonthlyAttendance;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Services\Compensation\CompensationResolver;
use App\Services\Compensation\ResolvedComponentLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmployeePayrollCalculator
{
    public function __construct(
        private readonly CompensationResolver $compensationResolver,
    ) {}

    public function calculate(Employee $employee, PayrollRun $run, MonthlyAttendance $attendance): CalculatedPayrollResult
    {
        $asOf = Carbon::create($run->year, $run->month)->endOfMonth();
        $resolved = $this->compensationResolver->resolveForEmployee($employee, $asOf);

        $compensationHistory = EmployeeCompensationHistory::query()
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $asOf)
            ->where(function ($query) use ($asOf) {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $asOf);
            })
            ->orderByDesc('effective_from')
            ->firstOrFail();

        $prorationFactor = $this->resolveProrationFactor($attendance);
        $lines = $this->buildCompensationLines($resolved->lines, $prorationFactor, $attendance);
        $lines = $this->applyAdjustments($lines, $employee, $run);
        $loanResult = $this->applyLoanDeductions($lines, $employee, $run);
        $lines = $loanResult['lines'];
        $loanData = $loanResult['installments'];

        $grossEarnings = $this->sumByType($lines, PayrollLineComponentType::EARNING);
        $grossDeductions = $this->sumByType($lines, PayrollLineComponentType::DEDUCTION);
        $employerContributions = $this->sumByType($lines, PayrollLineComponentType::EMPLOYER_CONTRIBUTION);
        $netPay = round($grossEarnings - $grossDeductions, 2);

        return new CalculatedPayrollResult(
            employee: $employee,
            attendance: $attendance,
            compensationHistory: $compensationHistory,
            lines: $lines,
            grossEarnings: $grossEarnings,
            grossDeductions: $grossDeductions,
            employerContributions: $employerContributions,
            netPay: $netPay,
            loanInstallments: $loanData,
            prorationFactor: $prorationFactor,
        );
    }

    private function resolveProrationFactor(MonthlyAttendance $attendance): float
    {
        $totalDays = (float) ($attendance->total_days ?? 0);
        $workedDays = (float) ($attendance->worked_days ?? 0);

        if ($totalDays <= 0) {
            return 1.0;
        }

        return round(min(1.0, $workedDays / $totalDays), 6);
    }

    /**
     * @param Collection<int, ResolvedComponentLine> $componentLines
     * @return array<int, array<string, mixed>>
     */
    private function buildCompensationLines(Collection $componentLines, float $prorationFactor, MonthlyAttendance $attendance): array
    {
        $lines = [];

        foreach ($componentLines as $line) {
            $monthlyAmount = (float) ($line->monthlyAmount ?? 0);
            $amount = round($monthlyAmount * $prorationFactor, 2);

            if ($amount == 0.0) {
                continue;
            }

            $lines[] = [
                'component_id' => $line->componentId,
                'component_name' => $line->componentName,
                'component_type' => $this->mapComponentType($line->componentType),
                'calculated_amount' => $amount,
                'calculation_basis' => [
                    'source' => 'compensation_structure',
                    'structure_source' => $line->source,
                    'calculation_type' => $line->calculationType->value,
                    'configured_value' => $line->value,
                    'monthly_amount' => $monthlyAmount,
                    'proration_factor' => $prorationFactor,
                    'worked_days' => $attendance->worked_days,
                    'total_days' => $attendance->total_days,
                ],
            ];
        }

        return $lines;
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     * @return array<int, array<string, mixed>>
     */
    private function applyAdjustments(array $lines, Employee $employee, PayrollRun $run): array
    {
        $adjustments = PayrollAdjustment::query()
            ->where('payroll_run_id', $run->id)
            ->where('employee_id', $employee->id)
            ->get();

        foreach ($adjustments as $adjustment) {
            $componentType = $adjustment->adjustment_type === PayrollAdjustmentType::ADDITION
                ? PayrollLineComponentType::EARNING
                : PayrollLineComponentType::DEDUCTION;

            $lines[] = [
                'component_id' => $adjustment->component_id,
                'component_name' => $adjustment->component?->component_name ?? 'Manual Adjustment',
                'component_type' => $componentType,
                'calculated_amount' => round((float) $adjustment->amount, 2),
                'calculation_basis' => [
                    'source' => 'payroll_adjustment',
                    'adjustment_id' => $adjustment->id,
                    'adjustment_type' => $adjustment->adjustment_type->value,
                    'remarks' => $adjustment->remarks,
                ],
            ];
        }

        return $lines;
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     * @return array{lines: array<int, array<string, mixed>>, installments: array<int, array<string, mixed>>}
     */
    private function applyLoanDeductions(array $lines, Employee $employee, PayrollRun $run): array
    {
        $installments = [];
        $runPeriod = ($run->year * 12) + $run->month;

        $loans = EmployeeLoan::query()
            ->where('employee_id', $employee->id)
            ->where('status', EmployeeLoanStatus::ACTIVE)
            ->get()
            ->filter(function (EmployeeLoan $loan) use ($runPeriod) {
                $startPeriod = ((int) $loan->start_year * 12) + (int) $loan->start_month;

                return $startPeriod <= $runPeriod && (float) $loan->remaining_amount > 0;
            });

        foreach ($loans as $loan) {
            if ($loan->installments()->where('payroll_run_id', $run->id)->exists()) {
                continue;
            }

            $deductionAmount = round(min((float) $loan->emi_amount, (float) $loan->remaining_amount), 2);

            if ($deductionAmount <= 0) {
                continue;
            }

            $installmentNo = (int) $loan->installments()->max('installment_no') + 1;

            $lines[] = [
                'component_id' => null,
                'component_name' => $loan->loan_name.' EMI',
                'component_type' => PayrollLineComponentType::DEDUCTION,
                'calculated_amount' => $deductionAmount,
                'calculation_basis' => [
                    'source' => 'employee_loan',
                    'loan_id' => $loan->id,
                    'installment_no' => $installmentNo,
                    'emi_amount' => (float) $loan->emi_amount,
                    'remaining_before' => (float) $loan->remaining_amount,
                ],
            ];

            $installments[] = [
                'loan' => $loan,
                'installment_no' => $installmentNo,
                'amount' => $deductionAmount,
            ];
        }

        return ['lines' => $lines, 'installments' => $installments];
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     */
    private function sumByType(array $lines, PayrollLineComponentType $type): float
    {
        return round(collect($lines)
            ->filter(fn (array $line) => $line['component_type'] === $type)
            ->sum('calculated_amount'), 2);
    }

    private function mapComponentType(ComponentType $type): PayrollLineComponentType
    {
        return match ($type) {
            ComponentType::EARNING => PayrollLineComponentType::EARNING,
            ComponentType::DEDUCTION => PayrollLineComponentType::DEDUCTION,
        };
    }
}
