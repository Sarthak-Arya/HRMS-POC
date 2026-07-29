<?php

namespace App\Models;

/**
 * Backward-compatible alias for {@see EmployeeAttendanceSummary}.
 *
 * @property float $casual_leave
 * @property float $earned_leave
 * @property float $sick_leave
 * @property float $holiday
 */
class MonthlyAttendance extends EmployeeAttendanceSummary
{
    public function getCasualLeaveAttribute(): float
    {
        return $this->leaveDaysForCode('CL');
    }

    public function getEarnedLeaveAttribute(): float
    {
        return $this->leaveDaysForCode('EL');
    }

    public function getSickLeaveAttribute(): float
    {
        return $this->leaveDaysForCode('SL');
    }

    public function getHolidayAttribute(): float
    {
        return (float) $this->holiday_days;
    }
}
