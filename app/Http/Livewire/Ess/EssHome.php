<?php

namespace App\Http\Livewire\Ess;

use App\Enums\Leave\LeaveRequestStatus;
use App\Http\Livewire\Ess\Concerns\ResolvesEmployeePortal;
use App\Models\CompanyHoliday;
use App\Models\EmployeeLeaveBalance;
use App\Models\EmployeePayroll;
use App\Models\LeaveRequest;
use App\Services\Ess\LeaveRequestService;
use App\Services\Ess\PunchService;
use Livewire\Component;

class EssHome extends Component
{
    use ResolvesEmployeePortal;

    public string $employeeName = '';

    public string $companyName = '';

    public string $todayLabel = '';

    public array $balances = [];

    public array $todayPunch = [];

    public int $pendingApprovals = 0;

    public ?array $latestPayslip = null;

    public array $upcomingHolidays = [];

    public array $recentLeaves = [];

    public function mount(?string $company_id = null): void
    {
        $employee = $this->bootEmployeePortal($company_id);
        $this->employeeName = $employee->employee_name;
        $this->companyName = $employee->company?->company_name ?? '';
        $this->todayLabel = now()->format('l, F j, Y');

        $year = (int) now()->year;
        $this->balances = EmployeeLeaveBalance::query()
            ->with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->get()
            ->map(fn (EmployeeLeaveBalance $b) => [
                'code' => $b->leaveType?->code ?? '—',
                'name' => $b->leaveType?->name ?? 'Leave',
                'closing' => (float) $b->closing_balance,
            ])
            ->all();

        $log = app(PunchService::class)->todaysLog($employee);
        $this->todayPunch = [
            'clock_in' => $log?->clock_in?->format('h:i A'),
            'clock_out' => $log?->clock_out?->format('h:i A'),
            'status' => ! $log ? 'not_in' : ($log->clock_out ? 'complete' : 'in'),
        ];

        if (auth()->user()?->hasPermission(\App\Enums\Permission::EssApprovals)
            || auth()->user()?->hasPermission(\App\Enums\Permission::EssApprovalsAny)) {
            $this->pendingApprovals = app(LeaveRequestService::class)
                ->pendingForApprover(auth()->user(), (int) $this->companyId)
                ->count();
        }

        $payroll = EmployeePayroll::query()
            ->with('payrollRun')
            ->where('employee_id', $employee->id)
            ->latest('id')
            ->first();

        if ($payroll && $payroll->payrollRun) {
            $this->latestPayslip = [
                'id' => $payroll->id,
                'run_id' => $payroll->payroll_run_id,
                'label' => sprintf('%s %s', date('F', mktime(0, 0, 0, $payroll->payrollRun->month, 1)), $payroll->payrollRun->year),
                'net' => (float) ($payroll->net_pay ?? 0),
            ];
        }

        $this->upcomingHolidays = CompanyHoliday::query()
            ->where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->whereDate('holiday_date', '>=', now()->toDateString())
            ->orderBy('holiday_date')
            ->limit(5)
            ->get()
            ->map(fn (CompanyHoliday $h) => [
                'date' => $h->holiday_date->format('M j'),
                'name' => $h->name,
            ])
            ->all();

        $this->recentLeaves = LeaveRequest::query()
            ->with('leaveType')
            ->where('employee_id', $employee->id)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (LeaveRequest $r) => [
                'type' => $r->leaveType?->code ?? 'Leave',
                'range' => $r->start_date->equalTo($r->end_date)
                    ? $r->start_date->format('M j')
                    : $r->start_date->format('M j').' – '.$r->end_date->format('M j'),
                'status' => $r->status instanceof LeaveRequestStatus ? $r->status->label() : (string) $r->status,
                'status_key' => $r->status instanceof LeaveRequestStatus ? $r->status->value : (string) $r->status,
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.ess.home');
    }
}
