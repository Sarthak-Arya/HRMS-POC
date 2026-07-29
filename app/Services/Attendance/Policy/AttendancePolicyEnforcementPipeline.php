<?php

namespace App\Services\Attendance\Policy;

use App\Enums\Attendance\AttendanceMode;
use App\Enums\Attendance\AttendanceStatus;
use App\Models\LeaveType;
use App\Services\Attendance\AttendanceCalendarService;
use App\Services\Attendance\LeaveAttendanceSyncService;
use App\Services\Attendance\LeaveBalanceResolver;
use App\Services\Attendance\LeaveExceptionRuleService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AttendancePolicyEnforcementPipeline
{
    public function __construct(
        private readonly AttendanceCalendarService $calendarService,
        private readonly LeaveBalanceResolver $leaveBalanceResolver,
        private readonly LeaveExceptionRuleService $exceptionRuleService,
        private readonly LeaveAttendanceSyncService $leaveSyncService,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function validateDailyRows(AttendancePolicyContext $context, array $rows, bool $allowOverride = false): array
    {
        $this->assertPolicyExists($context);
        $this->assertNotLocked($context);
        $this->assertCaptureMode($context, AttendanceMode::DAILY_MARKING);

        $policy = $context->policy;
        $employee = $context->employee;
        $month = $context->month;
        $year = $context->year;

        $dates = array_unique(array_column($rows, 'attendance_date'));
        $merged = $this->calendarService->applyCalendarDefaults($employee, $policy, $month, $year, $rows);

        $syncRows = $this->leaveSyncService->syncApprovedLeaveMarks($employee, $policy, $dates);
        $mergedByDate = collect($merged)->keyBy('attendance_date');
        foreach ($syncRows as $syncRow) {
            if (! $mergedByDate->has($syncRow['attendance_date'])
                || empty($mergedByDate->get($syncRow['attendance_date'])['attendance_status'])) {
                $mergedByDate->put($syncRow['attendance_date'], $syncRow);
            }
        }

        $normalized = [];
        $leaveDaysByCode = [];

        foreach ($mergedByDate as $row) {
            $validated = $this->validateDailyRow($row);
            $status = AttendanceStatus::from($validated['attendance_status']);

            if ($status === AttendanceStatus::HALF_DAY && ! $policy->allow_half_day) {
                throw ValidationException::withMessages([
                    'attendance' => "Half-day not allowed for employee {$employee->employee_code}.",
                ]);
            }

            if (in_array($status, [AttendanceStatus::LEAVE, AttendanceStatus::HALF_DAY], true)) {
                $code = $this->leaveCodeForRow($validated);
                $days = $status === AttendanceStatus::HALF_DAY ? 0.5 : 1.0;
                $leaveDaysByCode[$code] = ($leaveDaysByCode[$code] ?? 0) + $days;
            }

            $normalized[] = $validated;
        }

        $this->enforceLeaveBalances($context, $leaveDaysByCode);
        $this->exceptionRuleService->evaluateMonthly(
            $employee,
            $month,
            $year,
            $leaveDaysByCode,
            $context->exceptionRules,
            $allowOverride,
        );

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{validated: array<string, mixed>, leave_days: array<string, float>, warnings: list<string>}
     */
    public function validateMonthlyPayload(AttendancePolicyContext $context, array $payload, bool $allowOverride = false): array
    {
        $this->assertPolicyExists($context);
        $this->assertNotLocked($context);
        $this->assertCaptureMode($context, AttendanceMode::MONTHLY_SUMMARY);

        $validated = $this->validateMonthlyRow($payload);
        $leaveDays = $this->extractLeaveDays($validated, $context);

        $this->enforceLeaveBalances($context, $leaveDays);
        $warnings = $this->exceptionRuleService->evaluateMonthly(
            $context->employee,
            $context->month,
            $context->year,
            $leaveDays,
            $context->exceptionRules,
            $allowOverride,
        );

        return [
            'validated' => $validated,
            'leave_days' => $leaveDays,
            'warnings' => $warnings,
        ];
    }

    private function assertPolicyExists(AttendancePolicyContext $context): void
    {
        if (! $context->policy) {
            throw ValidationException::withMessages([
                'policy' => "No attendance policy assigned for employee {$context->employee->employee_code}.",
            ]);
        }
    }

    private function assertNotLocked(AttendancePolicyContext $context): void
    {
        if ($context->isSummaryLocked()) {
            throw ValidationException::withMessages([
                'attendance' => "Attendance for {$context->employee->employee_code} is locked for {$context->month}/{$context->year}.",
            ]);
        }
    }

    private function assertCaptureMode(AttendancePolicyContext $context, AttendanceMode $expected): void
    {
        if ($context->policy->attendance_mode !== $expected) {
            $mode = $expected === AttendanceMode::DAILY_MARKING ? 'daily marking' : 'monthly summary';

            throw ValidationException::withMessages([
                'attendance' => "Employee {$context->employee->employee_code} is not on a {$mode} policy.",
            ]);
        }
    }

    /**
     * @param  array<string, float>  $leaveDaysByCode
     */
    private function enforceLeaveBalances(AttendancePolicyContext $context, array $leaveDaysByCode): void
    {
        if ($leaveDaysByCode === []) {
            return;
        }

        $totalDays = array_sum($leaveDaysByCode);
        if ($totalDays <= 0) {
            return;
        }

        $this->leaveBalanceResolver->assertSufficientBalance(
            $context->employee,
            $context->policy,
            $leaveDaysByCode,
            $context->year,
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function validateDailyRow(array $row): array
    {
        $validator = Validator::make($row, [
            'employee_id' => 'required|integer',
            'attendance_date' => 'required|date',
            'attendance_status' => 'required|in:present,absent,half_day,leave,holiday,week_off',
            'leave_type_id' => 'nullable|integer|exists:leave_types,id',
            'first_half_status' => 'nullable|in:present,absent,leave,week_off',
            'second_half_status' => 'nullable|in:present,absent,leave,week_off',
            'remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function validateMonthlyRow(array $row): array
    {
        $validator = Validator::make($row, [
            'employee_id' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
            'cl' => 'nullable|numeric|min:0',
            'el' => 'nullable|numeric|min:0',
            'sl' => 'nullable|numeric|min:0',
            'lwp' => 'nullable|numeric|min:0',
            'leaves' => 'nullable|array',
            'leaves.*' => 'nullable|numeric|min:0',
            'esi_leave' => 'nullable|numeric|min:0',
            'holiday' => 'nullable|numeric|min:0',
            'tot_dys' => 'nullable|numeric|min:0',
            'working_days' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|array',
            'deductions.*' => 'numeric|min:0',
            'overtime_days' => 'nullable|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'shift_code' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function leaveCodeForRow(array $validated): string
    {
        if (! empty($validated['leave_type_id'])) {
            $lt = LeaveType::find($validated['leave_type_id']);

            return strtoupper($lt?->code ?? 'LWP');
        }

        return 'LWP';
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, float>
     */
    private function extractLeaveDays(array $validated, AttendancePolicyContext $context): array
    {
        if (isset($validated['leaves']) && is_array($validated['leaves'])) {
            $result = [];
            foreach ($validated['leaves'] as $code => $days) {
                $result[strtoupper((string) $code)] = (float) $days;
            }

            return $result;
        }

        $existing = $context->existingSummary;

        return [
            'CL' => (float) ($validated['cl'] ?? $existing?->casual_leave ?? 0),
            'EL' => (float) ($validated['el'] ?? $existing?->earned_leave ?? 0),
            'SL' => (float) ($validated['sl'] ?? $existing?->sick_leave ?? 0),
        ];
    }
}
