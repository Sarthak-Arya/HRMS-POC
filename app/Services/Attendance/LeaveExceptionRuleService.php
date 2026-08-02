<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\LeaveExceedAction;
use App\Enums\Attendance\LeaveExceptionPayrollAction;
use App\Models\AttendanceLeaveExceptionRule;
use App\Models\Employee;
use App\Models\LeaveType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LeaveExceptionRuleService
{
    /**
     * @return Collection<int, AttendanceLeaveExceptionRule>
     */
    public function listForCompany(int $companyId, ?int $policyId = null): Collection
    {
        return AttendanceLeaveExceptionRule::query()
            ->where('company_id', $companyId)
            ->when($policyId, fn ($q) => $q->where(function ($inner) use ($policyId) {
                $inner->whereNull('policy_id')->orWhere('policy_id', $policyId);
            }))
            ->with(['leaveType', 'policy'])
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $companyId, array $data): AttendanceLeaveExceptionRule
    {
        $validated = $this->validate($data);

        $rule = AttendanceLeaveExceptionRule::create(array_merge($validated, ['company_id' => $companyId]));

        app(AttendanceAuditService::class)->log(
            $companyId,
            'attendance_leave_exception_rule',
            $rule->id,
            'leave_exception_changed',
        );

        return $rule;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $companyId, int $ruleId, array $data): AttendanceLeaveExceptionRule
    {
        $rule = AttendanceLeaveExceptionRule::where('company_id', $companyId)->findOrFail($ruleId);
        $validated = $this->validate($data);
        $rule->update($validated);

        $fresh = $rule->fresh(['leaveType', 'policy']);

        app(AttendanceAuditService::class)->log(
            $companyId,
            'attendance_leave_exception_rule',
            $fresh->id,
            'leave_exception_changed',
        );

        return $fresh;
    }

    public function delete(int $companyId, int $ruleId): void
    {
        AttendanceLeaveExceptionRule::where('company_id', $companyId)->findOrFail($ruleId)->delete();

        app(AttendanceAuditService::class)->log(
            $companyId,
            'attendance_leave_exception_rule',
            $ruleId,
            'leave_exception_changed',
        );
    }

    /**
     * @param  array<string, float>  $leaveDaysByCode
     * @return list<string> warnings
     */
    public function evaluateMonthly(
        Employee $employee,
        int $month,
        int $year,
        array $leaveDaysByCode,
        Collection $rules,
        bool $allowOverride = false,
    ): array {
        $warnings = [];

        foreach ($leaveDaysByCode as $code => $days) {
            if ($days <= 0) {
                continue;
            }

            $leaveType = LeaveType::where('company_id', $employee->company_id)
                ->where('code', strtoupper($code))
                ->first();

            $applicable = $rules->filter(function (AttendanceLeaveExceptionRule $rule) use ($leaveType) {
                return $rule->leave_type_id === null
                    || ($leaveType && $rule->leave_type_id === $leaveType->id);
            });

            foreach ($applicable as $rule) {
                if ($rule->max_days_per_month !== null && $days > (float) $rule->max_days_per_month) {
                    $this->handleExceed(
                        $rule,
                        $employee,
                        $code,
                        $days,
                        (float) $rule->max_days_per_month,
                        'month',
                        $allowOverride,
                        $warnings,
                    );
                }
            }
        }

        return $warnings;
    }

    /**
     * @param  list<string>  $warnings
     */
    private function handleExceed(
        AttendanceLeaveExceptionRule $rule,
        Employee $employee,
        string $code,
        float $requested,
        float $limit,
        string $period,
        bool $allowOverride,
        array &$warnings,
    ): void {
        $message = "Employee {$employee->employee_code} exceeds {$code} {$period} limit ({$requested} > {$limit}).";

        match ($rule->on_exceed) {
            LeaveExceedAction::BLOCK => throw ValidationException::withMessages(['leave_balance' => $message]),
            LeaveExceedAction::WARN => $warnings[] = $message,
            LeaveExceedAction::REQUIRE_OVERRIDE => $allowOverride
                ? $warnings[] = $message.' Override applied.'
                : throw ValidationException::withMessages(['leave_balance' => $message.' Override required.']),
            LeaveExceedAction::ROUTE_TO_LWP => $warnings[] = $message.' Excess will route to LWP.',
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        $validator = Validator::make($data, [
            'policy_id' => 'nullable|integer|exists:attendance_policies,id',
            'leave_type_id' => 'nullable|integer|exists:leave_types,id',
            'max_days_per_month' => 'nullable|numeric|min:0',
            'max_days_per_year' => 'nullable|numeric|min:0',
            'on_exceed' => 'required|in:block,warn,route_to_lwp,require_override',
            'payroll_action' => 'nullable|in:none,lop_only,prorate_only',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $validated['on_exceed'] = LeaveExceedAction::from($validated['on_exceed'])->value;
        $validated['payroll_action'] = LeaveExceptionPayrollAction::from(
            $validated['payroll_action'] ?? LeaveExceptionPayrollAction::NONE->value
        )->value;

        return $validated;
    }
}
