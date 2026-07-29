<?php

namespace App\Services\Attendance;

use App\Models\AttendancePolicy;
use App\Models\Employee;
use App\Models\EmployeeAttendance;

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
     * Stub for future leave_requests integration — returns synthetic approved marks for testing.
     *
     * @param  list<array{date: string, leave_type_id: int}>  $approvedMarks
     * @return list<array{employee_id: int, attendance_date: string, attendance_status: string, leave_type_id: int}>
     */
    public function buildRowsFromApprovedMarks(Employee $employee, array $approvedMarks): array
    {
        $rows = [];
        foreach ($approvedMarks as $mark) {
            $rows[] = [
                'employee_id' => $employee->id,
                'attendance_date' => $mark['date'],
                'attendance_status' => 'leave',
                'leave_type_id' => $mark['leave_type_id'],
            ];
        }

        return $rows;
    }
}
