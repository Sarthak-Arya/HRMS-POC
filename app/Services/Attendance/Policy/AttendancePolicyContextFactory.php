<?php

namespace App\Services\Attendance\Policy;

use App\Models\AttendanceLeaveExceptionRule;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveType;
use App\Models\MonthlyAttendance;
use App\Services\Attendance\AttendancePolicyResolver;
use App\Services\Attendance\HolidayCalendarService;
use App\Services\Attendance\LeaveBalanceResolver;
use Carbon\Carbon;

class AttendancePolicyContextFactory
{
    public function __construct(
        private readonly AttendancePolicyResolver $policyResolver,
        private readonly HolidayCalendarService $holidayCalendarService,
        private readonly LeaveBalanceResolver $leaveBalanceResolver,
    ) {}

    public function forEmployeeMonth(Employee $employee, int $month, int $year, ?Carbon $asOf = null): AttendancePolicyContext
    {
        $asOf = $asOf ?? Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $employee->loadMissing(['department', 'designation', 'location']);

        $policy = $this->policyResolver->resolveForEmployee($employee, $asOf);

        $existingSummary = MonthlyAttendance::query()
            ->where('employee_id', $employee->id)
            ->where('company_id', $employee->company_id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        $weeklyOffDates = $policy
            ? $this->policyResolver->resolveWeeklyOffDates($employee, $month, $year, $asOf)
            : [];

        $holidayDates = $policy
            ? collect($this->policyResolver->resolveApplicableHolidays($employee, $month, $year, $asOf))
                ->map(fn ($h) => $h->holiday_date->toDateString())
                ->all()
            : [];

        if ($policy) {
            $this->leaveBalanceResolver->initializeBalancesForEmployee($employee, $policy, $year);
        }

        $leaveTypes = LeaveType::where('company_id', $employee->company_id)->where('is_active', true)->get();
        $balances = collect();
        foreach ($leaveTypes as $leaveType) {
            $balance = EmployeeLeaveBalance::query()
                ->where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $year)
                ->first();
            if ($balance) {
                $balances->put(strtoupper($leaveType->code), $balance);
            }
        }

        $exceptionRules = AttendanceLeaveExceptionRule::query()
            ->where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->where(function ($q) use ($policy) {
                $q->whereNull('policy_id');
                if ($policy) {
                    $q->orWhere('policy_id', $policy->id);
                }
            })
            ->with('leaveType')
            ->get();

        return new AttendancePolicyContext(
            employee: $employee,
            policy: $policy,
            asOf: $asOf,
            month: $month,
            year: $year,
            existingSummary: $existingSummary,
            weeklyOffDates: $weeklyOffDates,
            holidayDates: $holidayDates,
            leaveBalances: $balances,
            exceptionRules: $exceptionRules,
        );
    }
}
