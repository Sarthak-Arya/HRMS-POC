<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\AttendanceScopeType;
use App\Models\AttendancePolicy;
use App\Models\AttendancePolicyAssignment;
use App\Models\CompanyHoliday;
use App\Models\Employee;
use Carbon\Carbon;

class AttendancePolicyResolver
{
    public function resolveForEmployee(Employee $employee, ?Carbon $asOf = null): ?AttendancePolicy
    {
        $asOf = $asOf ?? Carbon::today();
        $employee->loadMissing(['department', 'designation', 'location']);

        foreach (AttendanceScopeType::cascadeOrder() as $scopeType) {
            $scopeId = $this->scopeIdForEmployee($employee, $scopeType);
            if ($scopeType !== AttendanceScopeType::COMPANY && $scopeId === null) {
                continue;
            }

            $assignment = $this->findActiveAssignment(
                (int) $employee->company_id,
                $scopeType,
                $scopeId,
                $asOf,
            );

            if ($assignment?->policy?->is_active) {
                return $assignment->policy;
            }
        }

        return null;
    }

    /**
     * @return array<string, ?array{policy_name: string, mode: string, source: string}>
     */
    public function policyInheritanceChain(Employee $employee, ?Carbon $asOf = null): array
    {
        $asOf = $asOf ?? Carbon::today();
        $employee->loadMissing(['department', 'designation', 'location']);
        $chain = [];

        foreach (AttendanceScopeType::cascadeOrder() as $scopeType) {
            $scopeId = $this->scopeIdForEmployee($employee, $scopeType);
            if ($scopeType !== AttendanceScopeType::COMPANY && $scopeId === null) {
                $chain[$scopeType->value] = null;

                continue;
            }

            $assignment = $this->findActiveAssignment(
                (int) $employee->company_id,
                $scopeType,
                $scopeId,
                $asOf,
            );

            $chain[$scopeType->value] = $assignment?->policy
                ? [
                    'policy_name' => $assignment->policy->policy_name,
                    'mode' => $assignment->policy->attendance_mode->value,
                    'source' => $scopeType->value,
                ]
                : null;
        }

        return $chain;
    }

    public function resolveWorkingDays(AttendancePolicy $policy, int $month, int $year): float
    {
        return match ($policy->working_days_basis) {
            \App\Enums\Attendance\WorkingDaysBasis::FIXED_26 => 26.0,
            \App\Enums\Attendance\WorkingDaysBasis::CALENDAR_DAYS => (float) Carbon::createFromDate($year, $month, 1)->daysInMonth,
            \App\Enums\Attendance\WorkingDaysBasis::CUSTOM => (float) ($policy->custom_working_days ?? 26),
        };
    }

    /**
     * @return list<string>
     */
    public function resolveWeeklyOffDates(Employee $employee, int $month, int $year, ?Carbon $asOf = null): array
    {
        $policy = $this->resolveForEmployee($employee, $asOf ?? Carbon::createFromDate($year, $month, 1)->endOfMonth());
        if (! $policy) {
            return [];
        }

        return app(WeeklyOffPatternService::class)->resolveWeeklyOffDates($policy, $month, $year);
    }

    /**
     * @return list<CompanyHoliday>
     */
    public function resolveApplicableHolidays(Employee $employee, int $month, int $year, ?Carbon $asOf = null): array
    {
        $policy = $this->resolveForEmployee($employee, $asOf ?? Carbon::createFromDate($year, $month, 1)->endOfMonth());
        if (! $policy) {
            return [];
        }

        return app(HolidayCalendarService::class)->resolveHolidaysForEmployee($employee, $policy, $month, $year);
    }

    public function resolveWorkingDaysFromCalendar(Employee $employee, int $month, int $year): float
    {
        $policy = $this->resolveForEmployee($employee, Carbon::createFromDate($year, $month, 1)->endOfMonth());
        if (! $policy) {
            return 26.0;
        }

        $calendarDays = (float) Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $weekOffCount = count($this->resolveWeeklyOffDates($employee, $month, $year));
        $holidayCount = count($this->resolveApplicableHolidays($employee, $month, $year));

        return max(0, $calendarDays - $weekOffCount - $holidayCount);
    }

    public function resolveMonthlyWorkingDays(Employee $employee, int $month, int $year, ?AttendancePolicy $policy = null): float
    {
        $policy ??= $this->resolveForEmployee($employee, Carbon::createFromDate($year, $month, 1)->endOfMonth());
        if (! $policy) {
            return 26.0;
        }

        if ($policy->working_days_basis === \App\Enums\Attendance\WorkingDaysBasis::CALENDAR_DAYS) {
            return $this->resolveWorkingDaysFromCalendar($employee, $month, $year);
        }

        return $this->resolveWorkingDays($policy, $month, $year);
    }

    private function scopeIdForEmployee(Employee $employee, AttendanceScopeType $scopeType): ?int
    {
        return match ($scopeType) {
            AttendanceScopeType::EMPLOYEE => (int) $employee->id,
            AttendanceScopeType::DESIGNATION => $employee->designation_id ? (int) $employee->designation_id : null,
            AttendanceScopeType::DEPARTMENT => $employee->department_id ? (int) $employee->department_id : null,
            AttendanceScopeType::LOCATION => $employee->location_id ? (int) $employee->location_id : null,
            AttendanceScopeType::COMPANY => null,
        };
    }

    private function findActiveAssignment(
        int $companyId,
        AttendanceScopeType $scopeType,
        ?int $scopeId,
        Carbon $asOf,
    ): ?AttendancePolicyAssignment {
        return AttendancePolicyAssignment::with('policy')
            ->where('company_id', $companyId)
            ->where('scope_type', $scopeType->value)
            ->when(
                $scopeType === AttendanceScopeType::COMPANY,
                fn ($q) => $q->whereNull('scope_id'),
                fn ($q) => $q->where('scope_id', $scopeId),
            )
            ->where('effective_from', '<=', $asOf->toDateString())
            ->where(function ($q) use ($asOf) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $asOf->toDateString());
            })
            ->orderByDesc('effective_from')
            ->first();
    }
}
