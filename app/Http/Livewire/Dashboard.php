<?php

namespace App\Http\Livewire;

use App\Enums\Payroll\PayrollRunStatus;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAttendanceSummary;
use App\Models\EmployeePayroll;
use App\Models\Location;
use App\Models\PayrollRun;
use App\Services\Payroll\PayrollReadinessService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Dashboard extends Component
{
    public string $companyId = '';

    public string $companyName = '';

    public string $todayLabel = '';

    public array $stats = [];

    public ?array $readiness = null;

    public ?array $attendanceSnapshot = null;

    public ?array $currentPayrollRun = null;

    public Collection $recentEmployees;

    public Collection $recentPayrollRuns;

    public function mount(?string $company_id = null): void
    {
        $this->todayLabel = now()->format('l, F j, Y');
        $this->recentEmployees = collect();
        $this->recentPayrollRuns = collect();

        $this->companyId = $company_id ?? (string) request()->session()->get('companyId', '');
        if ($this->companyId !== '') {
            request()->session()->put('companyId', $this->companyId);
        }

        $company = Company::find($this->companyId);
        $this->companyName = $company?->company_name ?? 'Dashboard';

        if (! $company) {
            Log::warning('Dashboard loaded with missing company', ['company_id' => $this->companyId]);
            $this->stats = $this->emptyStats();

            return;
        }

        $this->loadCompanyData($company);
    }

    public function render()
    {
        return view('livewire.dashboard');
    }

    private function loadCompanyData(Company $company): void
    {
        $employeesQuery = Employee::query()->where('company_id', $company->id);
        $total = (clone $employeesQuery)->count();
        $active = (clone $employeesQuery)->whereNull('dol')->count();

        $this->stats = [
            'total_employees' => $total,
            'active_employees' => $active,
            'inactive_employees' => $total - $active,
            'pf_employees' => (clone $employeesQuery)->whereNotNull('pf_no')->where('pf_no', '!=', '')->count(),
            'esi_employees' => (clone $employeesQuery)->whereNotNull('esi_no')->where('esi_no', '!=', '')->count(),
            'departments' => Department::where('company_id', $company->id)->count(),
            'locations' => Location::where('company_id', $company->id)->count(),
        ];

        $month = (int) now()->month;
        $year = (int) now()->year;
        $periodLabel = Carbon::create($year, $month)->format('F Y');

        $summaryCount = EmployeeAttendanceSummary::query()
            ->where('company_id', $company->id)
            ->where('month', $month)
            ->where('year', $year)
            ->count();

        $lockedCount = EmployeeAttendanceSummary::query()
            ->where('company_id', $company->id)
            ->where('month', $month)
            ->where('year', $year)
            ->whereNotNull('locked_at')
            ->count();

        $this->attendanceSnapshot = [
            'period_label' => $periodLabel,
            'summary_count' => $summaryCount,
            'locked_count' => $lockedCount,
            'active_employees' => $active,
            'coverage_pct' => $active > 0 ? (int) round(($summaryCount / $active) * 100) : 0,
            'locked_pct' => $summaryCount > 0 ? (int) round(($lockedCount / $summaryCount) * 100) : 0,
        ];

        $virtualRun = new PayrollRun([
            'company_id' => $company->id,
            'month' => $month,
            'year' => $year,
            'status' => PayrollRunStatus::DRAFT,
        ]);

        $assessment = app(PayrollReadinessService::class)->assess($virtualRun);

        $this->readiness = [
            'period_label' => $periodLabel,
            'total_employees' => $assessment['total_employees'],
            'ready_count' => $assessment['ready_count'],
            'ready_pct' => $assessment['total_employees'] > 0
                ? (int) round(($assessment['ready_count'] / $assessment['total_employees']) * 100)
                : 0,
            'is_ready' => $assessment['is_ready'],
            'unlocked_count' => $assessment['unlocked_summaries']->count(),
            'missing_attendance_count' => $assessment['missing_attendance']->count(),
            'missing_compensation_count' => $assessment['missing_compensation']->count(),
            'conflict_count' => count($assessment['reconciliation_conflicts']),
        ];

        $run = PayrollRun::query()
            ->where('company_id', $company->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($run) {
            $this->currentPayrollRun = [
                'id' => $run->id,
                'status' => strtolower($run->status->value),
                'status_label' => $run->status->value,
                'employee_count' => $run->employeePayrolls()->count(),
                'net_pay' => (float) EmployeePayroll::query()->where('payroll_run_id', $run->id)->sum('net_pay'),
                'period_label' => $periodLabel,
            ];
        }

        $this->recentPayrollRuns = PayrollRun::query()
            ->where('company_id', $company->id)
            ->withCount('employeePayrolls')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(5)
            ->get();

        $this->recentEmployees = Employee::with(['department', 'designation', 'location'])
            ->where('company_id', $company->id)
            ->orderByDesc('id')
            ->limit(6)
            ->get();
    }

    /**
     * @return array<string, int>
     */
    private function emptyStats(): array
    {
        return [
            'total_employees' => 0,
            'active_employees' => 0,
            'inactive_employees' => 0,
            'pf_employees' => 0,
            'esi_employees' => 0,
            'departments' => 0,
            'locations' => 0,
        ];
    }
}
