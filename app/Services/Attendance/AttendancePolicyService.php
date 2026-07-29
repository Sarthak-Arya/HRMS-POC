<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\AttendanceMode;
use App\Enums\Attendance\LeaveBalancePriorityMode;
use App\Enums\Attendance\WeeklyOffRule;
use App\Enums\Attendance\WorkingDaysBasis;
use App\Models\AttendancePolicy;
use App\Models\AttendancePolicyLeaveType;
use App\Models\LeaveType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AttendancePolicyService
{

    /**
     * @return Collection<int, AttendancePolicy>
     */
    public function listForCompany(int $companyId): Collection
    {
        return AttendancePolicy::query()
            ->where('company_id', $companyId)
            ->with(['policyLeaveTypes.leaveType'])
            ->orderBy('policy_name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{leave_type_id: int, annual_quota: float, carry_forward_limit?: float|null, encashable?: bool, deduction_priority?: int}>  $leaveRows
     */
    public function create(int $companyId, array $data, array $leaveRows = []): AttendancePolicy
    {
        $validated = $this->validatePolicy($data);

        return DB::transaction(function () use ($companyId, $validated, $leaveRows) {
            $policy = AttendancePolicy::create(array_merge($validated, ['company_id' => $companyId]));
            $this->syncLeaveTypes($policy, $leaveRows);
            $loaded = $policy->load(['policyLeaveTypes.leaveType']);

            app(AttendanceAuditService::class)->log(
                $companyId,
                'attendance_policy',
                $loaded->id,
                'policy_created',
                null,
                $loaded->only(['id', 'policy_name', 'attendance_mode', 'is_active']),
            );

            return $loaded;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{leave_type_id: int, annual_quota: float, carry_forward_limit?: float|null, encashable?: bool, deduction_priority?: int}>  $leaveRows
     */
    public function update(int $companyId, int $policyId, array $data, array $leaveRows = []): AttendancePolicy
    {
        $policy = AttendancePolicy::where('company_id', $companyId)->findOrFail($policyId);
        $validated = $this->validatePolicy($data, $policyId, $companyId);

        return DB::transaction(function () use ($policy, $validated, $leaveRows, $companyId) {
            $before = $policy->only(['id', 'policy_name', 'attendance_mode', 'is_active']);
            $policy->update($validated);
            $this->syncLeaveTypes($policy, $leaveRows);
            $loaded = $policy->fresh(['policyLeaveTypes.leaveType']);

            app(AttendanceAuditService::class)->log(
                $companyId,
                'attendance_policy',
                $loaded->id,
                'policy_updated',
                $before,
                $loaded->only(['id', 'policy_name', 'attendance_mode', 'is_active']),
            );

            return $loaded;
        });
    }

    public function deactivate(int $companyId, int $policyId): void
    {
        $policy = AttendancePolicy::where('company_id', $companyId)->findOrFail($policyId);
        $before = $policy->only(['id', 'policy_name', 'is_active']);
        $policy->update(['is_active' => false]);

        app(AttendanceAuditService::class)->log(
            $companyId,
            'attendance_policy',
            $policy->id,
            'policy_deactivated',
            $before,
            $policy->fresh()->only(['id', 'policy_name', 'is_active']),
        );
    }

    /**
     * @param  list<array{leave_type_id: int, annual_quota: float, carry_forward_limit?: float|null, encashable?: bool, deduction_priority?: int}>  $leaveRows
     */
    private function syncLeaveTypes(AttendancePolicy $policy, array $leaveRows): void
    {
        $keepIds = [];

        foreach ($leaveRows as $row) {
            if (empty($row['leave_type_id'])) {
                continue;
            }

            $leaveTypeId = (int) $row['leave_type_id'];
            $leaveType = LeaveType::where('company_id', $policy->company_id)->find($leaveTypeId);
            if (!$leaveType) {
                continue;
            }

            $record = AttendancePolicyLeaveType::updateOrCreate(
                ['policy_id' => $policy->id, 'leave_type_id' => $leaveTypeId],
                [
                    'annual_quota' => (float) ($row['annual_quota'] ?? 0),
                    'carry_forward_limit' => isset($row['carry_forward_limit']) && $row['carry_forward_limit'] !== ''
                        ? (float) $row['carry_forward_limit']
                        : null,
                    'encashable' => (bool) ($row['encashable'] ?? false),
                    'deduction_priority' => (int) ($row['deduction_priority'] ?? 100),
                ]
            );
            $keepIds[] = $record->id;
        }

        if ($keepIds !== []) {
            AttendancePolicyLeaveType::where('policy_id', $policy->id)
                ->whereNotIn('id', $keepIds)
                ->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validatePolicy(array $data, ?int $policyId = null, ?int $companyId = null): array
    {
        $validator = Validator::make($data, [
            'policy_name' => 'required|string|max:100',
            'attendance_mode' => 'required|in:monthly_summary,daily_marking,clock_in_out',
            'working_days_basis' => 'required|in:fixed_26,calendar_days,custom',
            'custom_working_days' => 'nullable|numeric|min:0|max:31',
            'weekly_off_rule' => 'required|in:sunday,sat_sun,alternate_saturday,custom',
            'custom_weekly_off_days' => 'nullable|array',
            'custom_weekly_off_days.*' => 'integer|min:0|max:6',
            'alternate_saturday_weeks' => 'nullable|array',
            'alternate_saturday_weeks.*' => 'integer|min:1|max:5',
            'grace_minutes' => 'nullable|integer|min:0|max:120',
            'min_half_day_minutes' => 'nullable|integer|min:0|max:720',
            'min_full_day_minutes' => 'nullable|integer|min:0|max:1440',
            'allow_overtime' => 'boolean',
            'allow_half_day' => 'boolean',
            'allow_negative_leave_balance' => 'boolean',
            'paid_holidays' => 'boolean',
            'paid_weekly_offs' => 'boolean',
            'require_attendance_before_holiday' => 'boolean',
            'require_attendance_after_holiday' => 'boolean',
            'auto_adjust_approved_leave' => 'boolean',
            'sandwich_leave_enabled' => 'boolean',
            'comp_off_enabled' => 'boolean',
            'leave_balance_priority_mode' => 'nullable|in:policy_order,strict_lwp_fallback',
            'auto_apply_week_offs' => 'boolean',
            'auto_apply_holidays' => 'boolean',
            'include_public_holidays' => 'boolean',
            'include_regional_holidays' => 'boolean',
            'include_emergency_holidays' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validator->after(function ($v) use ($data) {
            if (
                ($data['working_days_basis'] ?? '') === WorkingDaysBasis::CUSTOM->value
                && empty($data['custom_working_days'])
            ) {
                $v->errors()->add('custom_working_days', 'Custom working days is required when basis is custom.');
            }
            if (
                ($data['weekly_off_rule'] ?? '') === WeeklyOffRule::CUSTOM->value
                && empty($data['custom_weekly_off_days'])
            ) {
                $v->errors()->add('custom_weekly_off_days', 'Select at least one weekly off day.');
            }
            if (
                ($data['weekly_off_rule'] ?? '') === WeeklyOffRule::ALTERNATE_SATURDAY->value
                && empty($data['alternate_saturday_weeks'])
            ) {
                $v->errors()->add('alternate_saturday_weeks', 'Select at least one Saturday week pattern.');
            }
        });

        if ($policyId && $companyId) {
            $validator->after(function ($v) use ($data, $policyId, $companyId) {
                $exists = AttendancePolicy::where('company_id', $companyId)
                    ->where('policy_name', $data['policy_name'] ?? '')
                    ->where('id', '!=', $policyId)
                    ->exists();
                if ($exists) {
                    $v->errors()->add('policy_name', 'A policy with this name already exists.');
                }
            });
        } elseif ($companyId) {
            $validator->after(function ($v) use ($data, $companyId) {
                $exists = AttendancePolicy::where('company_id', $companyId)
                    ->where('policy_name', $data['policy_name'] ?? '')
                    ->exists();
                if ($exists) {
                    $v->errors()->add('policy_name', 'A policy with this name already exists.');
                }
            });
        }

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $validated['attendance_mode'] = AttendanceMode::from($validated['attendance_mode'])->value;
        $validated['working_days_basis'] = WorkingDaysBasis::from($validated['working_days_basis'])->value;
        $validated['weekly_off_rule'] = WeeklyOffRule::from($validated['weekly_off_rule'])->value;
        $validated['leave_balance_priority_mode'] = LeaveBalancePriorityMode::from(
            $validated['leave_balance_priority_mode'] ?? LeaveBalancePriorityMode::POLICY_ORDER->value
        )->value;

        return $validated;
    }
}
