<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\AttendanceEntrySource;
use App\Enums\Attendance\AttendanceMode;
use App\Models\AttendancePolicy;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceSummary;
use App\Models\MonthlyAttendance;
use App\Services\Attendance\Policy\AttendancePolicyContextFactory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceSummaryAggregationService
{
    public function __construct(
        private readonly SandwichLeaveService $sandwichLeaveService,
        private readonly CompOffService $compOffService,
        private readonly LeaveBalanceResolver $leaveBalanceResolver,
        private readonly AttendancePolicyContextFactory $contextFactory,
        private readonly AttendanceSummaryCalculator $summaryCalculator,
    ) {}

    public function aggregateMonthForEmployee(Employee $employee, int $month, int $year): ?EmployeeAttendanceSummary
    {
        $resolver = app(AttendancePolicyResolver::class);
        $policy = $resolver->resolveForEmployee(
            $employee,
            Carbon::createFromDate($year, $month, 1)->endOfMonth(),
        );

        if (! $policy || $policy->attendance_mode !== AttendanceMode::DAILY_MARKING) {
            return null;
        }

        $existing = MonthlyAttendance::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($existing && app(AttendanceService::class)->isSummaryLocked($existing)) {
            return $existing;
        }

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $records = EmployeeAttendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->with('leaveType')
            ->get();

        $records = $this->sandwichLeaveService->applySandwichRules($records, $policy, $month, $year, $employee);
        $this->compOffService->processAccruals($employee, $policy, $records);

        $context = $this->contextFactory->forEmployeeMonth($employee, $month, $year);
        $calculated = $this->summaryCalculator->fromDailyRecords($context, $records);

        return DB::transaction(function () use ($employee, $month, $year, $policy, $calculated) {
            $summary = MonthlyAttendance::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'company_id' => $employee->company_id,
                    'month' => $month,
                    'year' => $year,
                ],
                [
                    'policy_id' => $policy->id,
                    'entry_source' => AttendanceEntrySource::DAILY_AGGREGATED->value,
                    'calendar_days' => $calculated['calendar_days'],
                    'working_days' => $calculated['working_days'],
                    'present_days' => $calculated['present_days'],
                    'half_days' => $calculated['half_days'],
                    'paid_leave_days' => $calculated['paid_leave_days'],
                    'lop_days' => $calculated['lop_days'],
                    'weekly_off_days' => $calculated['weekly_off_days'],
                    'holiday_days' => $calculated['holiday_days'],
                    'worked_days' => $calculated['worked_days'],
                    'total_days' => $calculated['total_days'],
                ]
            );

            $summary->syncLeaveBreakdown($calculated['leave_by_code']);

            return $summary->fresh(['leaveBreakdown.leaveType']);
        });
    }

    /**
     * @param  list<int>|null  $employeeIds
     */
    public function aggregateMonthForCompany(int $companyId, int $month, int $year, ?array $employeeIds = null): int
    {
        $query = Employee::query()
            ->where('company_id', $companyId)
            ->whereNull('dol');

        if ($employeeIds !== null) {
            $query->whereIn('id', $employeeIds);
        }

        $count = 0;
        foreach ($query->get() as $employee) {
            if ($this->aggregateMonthForEmployee($employee, $month, $year)) {
                $this->leaveBalanceResolver->rebuildBalancesFromDailyRecords($employee, $year);
                $count++;
            }
        }

        return $count;
    }
}
