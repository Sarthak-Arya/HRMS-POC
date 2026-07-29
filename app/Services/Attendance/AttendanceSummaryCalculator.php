<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\AttendanceMode;
use App\Enums\Attendance\AttendanceStatus;
use App\Models\EmployeeAttendance;
use App\Models\LeaveType;
use App\Services\Attendance\Policy\AttendancePolicyContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceSummaryCalculator
{
    public function __construct(
        private readonly AttendancePolicyResolver $policyResolver,
    ) {}

    /**
     * @param  Collection<int, EmployeeAttendance>  $records
     * @return array{
     *     calendar_days: float,
     *     working_days: float,
     *     present_days: float,
     *     half_days: float,
     *     paid_leave_days: float,
     *     lop_days: float,
     *     weekly_off_days: float,
     *     holiday_days: float,
     *     worked_days: float,
     *     total_days: float,
     *     leave_by_code: array<string, float>
     * }
     */
    public function fromDailyRecords(
        AttendancePolicyContext $context,
        Collection $records,
    ): array {
        $policy = $context->policy;
        $month = $context->month;
        $year = $context->year;

        $calendarDays = (float) Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $workingDays = $policy
            ? $this->policyResolver->resolveWorkingDays($policy, $month, $year)
            : 26.0;

        $presentDays = 0.0;
        $halfDays = 0.0;
        $paidLeaveDays = 0.0;
        $lopDays = 0.0;
        $weeklyOffDays = 0.0;
        $holidayDays = 0.0;
        /** @var array<string, float> $leaveByCode */
        $leaveByCode = [];

        foreach ($records as $record) {
            $status = $record->attendance_status;

            if ($status === AttendanceStatus::PRESENT) {
                $presentDays += 1.0;
            } elseif ($status === AttendanceStatus::HALF_DAY) {
                $halfDays += 1.0;
                $this->accumulateHalfDay($record, $leaveByCode, $presentDays, $paidLeaveDays, $lopDays);
            } elseif ($status === AttendanceStatus::ABSENT) {
                $lopDays += 1.0;
            } elseif ($status === AttendanceStatus::WEEK_OFF) {
                $weeklyOffDays += 1.0;
                if ($policy && ! $policy->paid_weekly_offs) {
                    $lopDays += 1.0;
                }
            } elseif ($status === AttendanceStatus::HOLIDAY) {
                $holidayDays += 1.0;
                if ($policy && ! $policy->paid_holidays) {
                    $lopDays += 1.0;
                }
            } elseif ($status === AttendanceStatus::LEAVE) {
                $this->accumulateLeave($record, $leaveByCode, $paidLeaveDays, $lopDays);
            }
        }

        $workedDays = max(0, $presentDays);

        return [
            'calendar_days' => $calendarDays,
            'working_days' => $workingDays,
            'present_days' => $presentDays,
            'half_days' => $halfDays,
            'paid_leave_days' => $paidLeaveDays,
            'lop_days' => $lopDays,
            'weekly_off_days' => $weeklyOffDays,
            'holiday_days' => $holidayDays,
            'worked_days' => $workedDays,
            'total_days' => $workingDays,
            'leave_by_code' => $leaveByCode,
        ];
    }

    /**
     * @param  array<string, float>  $leaveDays
     * @return array<string, mixed>
     */
    public function fromMonthlyPayload(
        AttendancePolicyContext $context,
        array $validated,
        array $leaveDays,
        ?float $esiLeave = null,
        ?float $holiday = null,
    ): array {
        $policy = $context->policy;
        $employee = $context->employee;
        $month = $context->month;
        $year = $context->year;

        $esiLeave = $esiLeave ?? (float) ($validated['esi_leave'] ?? 0);
        $holiday = $holiday ?? (float) ($validated['holiday'] ?? 0);
        $totalDays = (float) ($validated['tot_dys'] ?? 0);
        $explicitWorkingDays = isset($validated['working_days']) ? (float) $validated['working_days'] : null;

        if ($policy) {
            $workingDays = $this->policyResolver->resolveMonthlyWorkingDays($employee, $month, $year, $policy);
        } else {
            $workingDays = $explicitWorkingDays ?? ($totalDays > 0 ? $totalDays : 26.0);
        }

        if ($policy && $holiday <= 0) {
            $holiday = (float) app(HolidayCalendarService::class)->countHolidaysForMonth(
                (int) $employee->company_id,
                $month,
                $year,
            );
        }

        $calendarDays = (float) Carbon::createFromDate($year, $month, 1)->daysInMonth;

        $paidLeave = 0.0;
        $lopDays = 0.0;
        foreach ($leaveDays as $code => $days) {
            $lt = LeaveType::where('company_id', $employee->company_id)->where('code', $code)->first();
            if ($lt?->is_paid) {
                $paidLeave += $days;
            } else {
                $lopDays += $days;
            }
        }

        $workedDays = max(0, $workingDays - ($lopDays + $esiLeave + $holiday));

        return [
            'calendar_days' => $calendarDays,
            'working_days' => $workingDays,
            'present_days' => $workedDays,
            'half_days' => 0.0,
            'paid_leave_days' => $paidLeave,
            'lop_days' => $lopDays,
            'weekly_off_days' => 0.0,
            'holiday_days' => $holiday,
            'worked_days' => $workedDays,
            'total_days' => $totalDays > 0 ? $totalDays : $workingDays,
            'leave_by_code' => $leaveDays,
            'esi_la' => $esiLeave,
        ];
    }

    /**
     * @param  array<string, float>  $leaveByCode
     */
    private function accumulateHalfDay(
        EmployeeAttendance $record,
        array &$leaveByCode,
        float &$presentDays,
        float &$paidLeaveDays,
        float &$lopDays,
    ): void {
        $firstHalf = $record->first_half_status?->value ?? AttendanceStatus::PRESENT->value;
        $secondHalf = $record->second_half_status?->value ?? AttendanceStatus::ABSENT->value;

        foreach ([$firstHalf, $secondHalf] as $half) {
            if ($half === AttendanceStatus::PRESENT->value) {
                $presentDays += 0.5;
            } elseif ($half === AttendanceStatus::LEAVE->value) {
                $code = strtoupper($record->leaveType?->code ?? 'LWP');
                $leaveByCode[$code] = ($leaveByCode[$code] ?? 0) + 0.5;
                if ($record->leaveType?->is_paid ?? false) {
                    $paidLeaveDays += 0.5;
                } else {
                    $lopDays += 0.5;
                }
            } else {
                $lopDays += 0.5;
            }
        }
    }

    /**
     * @param  array<string, float>  $leaveByCode
     */
    private function accumulateLeave(
        EmployeeAttendance $record,
        array &$leaveByCode,
        float &$paidLeaveDays,
        float &$lopDays,
    ): void {
        $code = strtoupper($record->leaveType?->code ?? 'LWP');
        $leaveByCode[$code] = ($leaveByCode[$code] ?? 0) + 1.0;

        if ($record->leaveType?->is_paid ?? false) {
            $paidLeaveDays += 1.0;
        } else {
            $lopDays += 1.0;
        }
    }
}
