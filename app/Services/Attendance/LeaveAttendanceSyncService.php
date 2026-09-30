<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\AttendanceStatus;
use App\Models\AttendancePolicy;
use App\Models\Employee;

class LeaveAttendanceSyncService
{
    public function __construct(
        private readonly AttendanceCalendarService $calendarService,
    ) {}

    /**
     * @param  list<string>  $dates
     * @return list<array{employee_id: int, attendance_date: string, attendance_status: string, leave_type_id?: int|null}>
     */
    public function syncApprovedLeaveMarks(Employee $employee, AttendancePolicy $policy, array $dates): array
    {
        return $this->calendarService->syncApprovedLeaveMarks($employee, $policy, $dates);
    }

    /**
     * Build daily attendance rows from approved leave request marks.
     *
     * @param  list<array{
     *     date: string,
     *     leave_type_id: int,
     *     attendance_status?: string,
     *     first_half_status?: string,
     *     second_half_status?: string
     * }>  $approvedMarks
     * @return list<array<string, mixed>>
     */
    public function buildRowsFromApprovedMarks(Employee $employee, array $approvedMarks): array
    {
        $rows = [];
        foreach ($approvedMarks as $mark) {
            $row = [
                'employee_id' => $employee->id,
                'attendance_date' => $mark['date'],
                'attendance_status' => $mark['attendance_status'] ?? AttendanceStatus::LEAVE->value,
                'leave_type_id' => $mark['leave_type_id'],
            ];

            if (($row['attendance_status'] === AttendanceStatus::HALF_DAY->value)) {
                $row['first_half_status'] = $mark['first_half_status'] ?? AttendanceStatus::LEAVE->value;
                $row['second_half_status'] = $mark['second_half_status'] ?? AttendanceStatus::PRESENT->value;
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
