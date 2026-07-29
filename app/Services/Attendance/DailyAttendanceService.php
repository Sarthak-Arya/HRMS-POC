<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\AttendanceRecordSource;
use App\Enums\Attendance\AttendanceStatus;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DailyAttendanceService
{
    /**
     * @param  list<int>  $employeeIds
     * @return Collection<int, EmployeeAttendance>
     */
    public function recordsForPeriod(
        int $companyId,
        array $employeeIds,
        string $dateFrom,
        string $dateTo,
    ): Collection {
        return EmployeeAttendance::query()
            ->where('company_id', $companyId)
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->with(['leaveType'])
            ->get();
    }

    /**
     * @param  list<array<string, mixed>>  $rows  pre-validated by enforcement pipeline
     */
    public function persistValidatedRows(int $companyId, array $rows): int
    {
        $saved = 0;

        foreach ($rows as $row) {
            $status = AttendanceStatus::from($row['attendance_status']);
            $leaveTypeId = in_array($status, [AttendanceStatus::LEAVE, AttendanceStatus::HALF_DAY], true)
                ? ($row['leave_type_id'] ?? null)
                : null;

            EmployeeAttendance::updateOrCreate(
                [
                    'employee_id' => (int) $row['employee_id'],
                    'attendance_date' => $row['attendance_date'],
                ],
                [
                    'company_id' => $companyId,
                    'attendance_status' => $status->value,
                    'leave_type_id' => $leaveTypeId,
                    'first_half_status' => $status === AttendanceStatus::HALF_DAY
                        ? ($row['first_half_status'] ?? AttendanceStatus::PRESENT->value)
                        : null,
                    'second_half_status' => $status === AttendanceStatus::HALF_DAY
                        ? ($row['second_half_status'] ?? AttendanceStatus::LEAVE->value)
                        : null,
                    'remarks' => $row['remarks'] ?? null,
                    'source' => AttendanceRecordSource::MANUAL->value,
                ]
            );

            $saved++;
        }

        return $saved;
    }

    /**
     * @param  list<array{employee_id: int, attendance_date: string, attendance_status: string, leave_type_id?: int|null, remarks?: string|null}>  $rows
     * @return array{saved: int, aggregated: int}
     *
     * @deprecated Use AttendanceCommandService::saveDaily
     */
    public function bulkUpsert(int $companyId, int $month, int $year, array $rows): array
    {
        return app(AttendanceCommandService::class)->saveDaily($companyId, $month, $year, $rows);
    }

    /**
     * @return array<string, array{status: string, is_auto: bool}>
     */
    public function calendarDefaultsForEmployee(
        Employee $employee,
        int $month,
        int $year,
        array $dates,
    ): array {
        $policy = app(AttendancePolicyResolver::class)->resolveForEmployee($employee);
        if (! $policy) {
            return [];
        }

        return app(AttendanceCalendarService::class)->defaultStatusesForPeriod($employee, $policy, $month, $year, $dates);
    }

    /** @return list<string> */
    public function datesInRange(int $month, int $year, int $rangeStartDay, int $rangeEndDay): array
    {
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $start = max(1, min($rangeStartDay, $daysInMonth));
        $end = max($start, min($rangeEndDay, $daysInMonth));
        $dates = [];

        for ($d = $start; $d <= $end; $d++) {
            $dates[] = Carbon::createFromDate($year, $month, $d)->toDateString();
        }

        return $dates;
    }
}
