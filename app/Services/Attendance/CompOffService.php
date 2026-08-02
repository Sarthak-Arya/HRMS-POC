<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\AttendanceStatus;
use App\Enums\Attendance\CompOffEntryType;
use App\Models\AttendancePolicy;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeCompOffLedger;
use Carbon\Carbon;

class CompOffService
{
    public function __construct(
        private readonly HolidayCalendarService $holidayCalendarService,
        private readonly WeeklyOffPatternService $weeklyOffPatternService,
        private readonly AttendanceAuditService $auditService,
    ) {}

    public function availableBalance(Employee $employee): float
    {
        $earned = (float) EmployeeCompOffLedger::where('employee_id', $employee->id)
            ->where('entry_type', CompOffEntryType::EARNED->value)
            ->sum('days');

        $used = (float) EmployeeCompOffLedger::where('employee_id', $employee->id)
            ->whereIn('entry_type', [CompOffEntryType::USED->value, CompOffEntryType::EXPIRED->value])
            ->sum('days');

        return max(0, $earned - $used);
    }

    public function isEligibleForCompOff(
        Employee $employee,
        AttendancePolicy $policy,
        EmployeeAttendance $record,
    ): bool {
        if (! $policy->comp_off_enabled) {
            return false;
        }

        if ($record->attendance_status !== AttendanceStatus::PRESENT) {
            return false;
        }

        $date = $record->attendance_date;
        $month = (int) $date->month;
        $year = (int) $date->year;

        $holidayDates = $this->holidayCalendarService->resolveHolidayDatesForEmployee(
            $employee,
            $policy,
            $month,
            $year,
        );

        $weekOffDates = $this->weeklyOffPatternService->resolveWeeklyOffDates($policy, $month, $year);
        $dateStr = $date->toDateString();

        if (! in_array($dateStr, $holidayDates, true) && ! in_array($dateStr, $weekOffDates, true)) {
            return false;
        }

        if ($policy->require_attendance_before_holiday || $policy->require_attendance_after_holiday) {
            return $this->meetsAdjacentAttendanceRequirement($employee, $policy, $date);
        }

        return true;
    }

    public function creditCompOff(
        Employee $employee,
        Carbon $date,
        float $days = 1.0,
        ?int $attendanceId = null,
        ?string $reason = null,
        bool $emitTelemetry = true,
    ): EmployeeCompOffLedger {
        $entry = EmployeeCompOffLedger::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'reference_date' => $date->toDateString(),
            'entry_type' => CompOffEntryType::EARNED->value,
            'days' => $days,
            'reason' => $reason ?? 'Worked on off-day/holiday',
            'attendance_id' => $attendanceId,
        ]);

        if ($emitTelemetry) {
            $this->auditService->log(
                (int) $employee->company_id,
                'employee_comp_off_ledger',
                $entry->id,
                'compoff_changed',
            );
        }

        return $entry;
    }

    public function debitCompOff(
        Employee $employee,
        float $days,
        Carbon $date,
        ?string $reason = null,
        bool $emitTelemetry = true,
    ): void {
        $entry = EmployeeCompOffLedger::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'reference_date' => $date->toDateString(),
            'entry_type' => CompOffEntryType::USED->value,
            'days' => $days,
            'reason' => $reason ?? 'Comp-off consumed',
        ]);

        if ($emitTelemetry) {
            $this->auditService->log(
                (int) $employee->company_id,
                'employee_comp_off_ledger',
                $entry->id,
                'compoff_changed',
            );
        }
    }

    /**
     * Process comp-off accrual for present records on holidays/week-offs.
     *
     * @param  iterable<EmployeeAttendance>  $records
     */
    public function processAccruals(
        Employee $employee,
        AttendancePolicy $policy,
        iterable $records,
    ): int {
        if (! $policy->comp_off_enabled) {
            return 0;
        }

        $credited = 0;

        foreach ($records as $record) {
            if (! $this->isEligibleForCompOff($employee, $policy, $record)) {
                continue;
            }

            $alreadyCredited = EmployeeCompOffLedger::where('employee_id', $employee->id)
                ->where('attendance_id', $record->id)
                ->where('entry_type', CompOffEntryType::EARNED->value)
                ->exists();

            if ($alreadyCredited) {
                continue;
            }

            $this->creditCompOff(
                $employee,
                $record->attendance_date,
                1.0,
                $record->id,
                null,
                false,
            );
            $credited++;
        }

        if ($credited > 0) {
            $this->auditService->log(
                (int) $employee->company_id,
                'employee_comp_off_ledger',
                null,
                'compoff_changed',
                null,
                null,
                [
                    'processed_count' => $credited,
                    'row_count' => $credited,
                ],
            );
        }

        return $credited;
    }

    private function meetsAdjacentAttendanceRequirement(
        Employee $employee,
        AttendancePolicy $policy,
        Carbon $date,
    ): bool {
        $beforeOk = true;
        $afterOk = true;

        if ($policy->require_attendance_before_holiday) {
            $before = EmployeeAttendance::where('employee_id', $employee->id)
                ->where('attendance_date', $date->copy()->subDay()->toDateString())
                ->first();
            $beforeOk = $before && $before->attendance_status === AttendanceStatus::PRESENT;
        }

        if ($policy->require_attendance_after_holiday) {
            $after = EmployeeAttendance::where('employee_id', $employee->id)
                ->where('attendance_date', $date->copy()->addDay()->toDateString())
                ->first();
            $afterOk = $after && $after->attendance_status === AttendanceStatus::PRESENT;
        }

        return $beforeOk && $afterOk;
    }
}
