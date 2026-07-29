<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\AttendanceStatus;
use App\Models\AttendancePolicy;
use App\Models\Employee;
use App\Models\EmployeeAttendance;

class AttendanceCalendarService
{
    public function __construct(
        private readonly WeeklyOffPatternService $weeklyOffPatternService,
        private readonly HolidayCalendarService $holidayCalendarService,
    ) {}

    /**
     * @return array<string, array{status: string, is_auto: bool}>
     */
    public function defaultStatusesForPeriod(
        Employee $employee,
        AttendancePolicy $policy,
        int $month,
        int $year,
        array $dates,
    ): array {
        $result = [];
        $weekOffDates = $policy->auto_apply_week_offs
            ? $this->weeklyOffPatternService->resolveWeeklyOffDates($policy, $month, $year)
            : [];
        $holidayDates = $policy->auto_apply_holidays
            ? $this->holidayCalendarService->resolveHolidayDatesForEmployee($employee, $policy, $month, $year)
            : [];

        foreach ($dates as $date) {
            if (in_array($date, $holidayDates, true)) {
                $result[$date] = ['status' => AttendanceStatus::HOLIDAY->value, 'is_auto' => true];
            } elseif (in_array($date, $weekOffDates, true)) {
                $result[$date] = ['status' => AttendanceStatus::WEEK_OFF->value, 'is_auto' => true];
            } else {
                $result[$date] = ['status' => '', 'is_auto' => false];
            }
        }

        return $result;
    }

    /**
     * Apply calendar defaults to daily matrix without overwriting manual entries.
     *
     * @param  list<array{employee_id: int, attendance_date: string, attendance_status: string, leave_type_id?: int|null}>  $rows
     * @return list<array{employee_id: int, attendance_date: string, attendance_status: string, leave_type_id?: int|null}>
     */
    public function applyCalendarDefaults(
        Employee $employee,
        AttendancePolicy $policy,
        int $month,
        int $year,
        array $rows,
    ): array {
        $dates = array_unique(array_column($rows, 'attendance_date'));
        $defaults = $this->defaultStatusesForPeriod($employee, $policy, $month, $year, $dates);
        $existing = collect($rows)->keyBy('attendance_date');

        $merged = [];
        foreach ($dates as $date) {
            $row = $existing->get($date, [
                'employee_id' => $employee->id,
                'attendance_date' => $date,
                'attendance_status' => '',
            ]);

            if (empty($row['attendance_status']) && ! empty($defaults[$date]['status'])) {
                $row['attendance_status'] = $defaults[$date]['status'];
            }

            $merged[] = $row;
        }

        return $merged;
    }

    /**
     * Sync approved leave marks from existing leave daily records when auto_adjust is enabled.
     *
     * @param  list<string>  $dates
     * @return list<array{employee_id: int, attendance_date: string, attendance_status: string, leave_type_id?: int|null}>
     */
    public function syncApprovedLeaveMarks(
        Employee $employee,
        AttendancePolicy $policy,
        array $dates,
    ): array {
        if (! $policy->auto_adjust_approved_leave) {
            return [];
        }

        $existing = EmployeeAttendance::query()
            ->where('employee_id', $employee->id)
            ->whereIn('attendance_date', $dates)
            ->where('attendance_status', AttendanceStatus::LEAVE->value)
            ->get();

        return $existing->map(fn (EmployeeAttendance $r) => [
            'employee_id' => $employee->id,
            'attendance_date' => $r->attendance_date->toDateString(),
            'attendance_status' => AttendanceStatus::LEAVE->value,
            'leave_type_id' => $r->leave_type_id,
        ])->all();
    }
}
