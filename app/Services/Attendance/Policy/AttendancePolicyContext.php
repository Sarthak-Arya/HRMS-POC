<?php

namespace App\Services\Attendance\Policy;

use App\Models\AttendanceLeaveExceptionRule;
use App\Models\AttendancePolicy;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\MonthlyAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendancePolicyContext
{
    /**
     * @param  list<string>  $weeklyOffDates
     * @param  list<string>  $holidayDates
     * @param  Collection<string, EmployeeLeaveBalance>  $leaveBalances  keyed by leave type code
     * @param  Collection<int, AttendanceLeaveExceptionRule>  $exceptionRules
     */
    public function __construct(
        public readonly Employee $employee,
        public readonly ?AttendancePolicy $policy,
        public readonly Carbon $asOf,
        public readonly int $month,
        public readonly int $year,
        public readonly ?MonthlyAttendance $existingSummary,
        public readonly array $weeklyOffDates,
        public readonly array $holidayDates,
        public readonly Collection $leaveBalances,
        public readonly Collection $exceptionRules,
    ) {}

    public function isSummaryLocked(): bool
    {
        return $this->existingSummary?->locked_at !== null;
    }
}
