<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\AttendanceStatus;
use App\Models\AttendancePolicy;
use App\Models\EmployeeAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SandwichLeaveService
{
    public function __construct(
        private readonly WeeklyOffPatternService $weeklyOffPatternService,
        private readonly HolidayCalendarService $holidayCalendarService,
    ) {}

    /**
     * Expand leave records to include sandwiched week-offs/holidays between leave days.
     *
     * @param  Collection<int, EmployeeAttendance>  $records
     * @return Collection<int, EmployeeAttendance>
     */
    public function applySandwichRules(
        Collection $records,
        AttendancePolicy $policy,
        int $month,
        int $year,
        ?\App\Models\Employee $employee = null,
    ): Collection {
        if (! $policy->sandwich_leave_enabled || $records->isEmpty()) {
            return $records;
        }

        $leaveDates = $records
            ->filter(fn (EmployeeAttendance $r) => $r->attendance_status === AttendanceStatus::LEAVE)
            ->map(fn (EmployeeAttendance $r) => $r->attendance_date->toDateString())
            ->sort()
            ->values();

        if ($leaveDates->count() < 2) {
            return $records;
        }

        $weekOffDates = $this->weeklyOffPatternService->resolveWeeklyOffDates($policy, $month, $year);
        $holidayDates = $employee
            ? $this->holidayCalendarService->resolveHolidayDatesForEmployee($employee, $policy, $month, $year)
            : [];

        $sandwichableDates = array_unique(array_merge($weekOffDates, $holidayDates));
        $indexed = $records->keyBy(fn (EmployeeAttendance $r) => $r->attendance_date->toDateString());

        $firstLeave = Carbon::parse($leaveDates->first());
        $lastLeave = Carbon::parse($leaveDates->last());

        foreach ($sandwichableDates as $dateStr) {
            $date = Carbon::parse($dateStr);
            if ($date->between($firstLeave, $lastLeave) && ! $indexed->has($dateStr)) {
                $synthetic = new EmployeeAttendance([
                    'attendance_date' => $dateStr,
                    'attendance_status' => AttendanceStatus::LEAVE,
                    'leave_type_id' => $records->firstWhere('attendance_status', AttendanceStatus::LEAVE)?->leave_type_id,
                    'source' => 'calculated',
                ]);
                $indexed->put($dateStr, $synthetic);
            }
        }

        return $indexed->sortBy(fn (EmployeeAttendance $r) => $r->attendance_date->toDateString())->values();
    }
}
