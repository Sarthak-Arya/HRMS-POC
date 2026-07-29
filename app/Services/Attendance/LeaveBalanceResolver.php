<?php

namespace App\Services\Attendance;

use App\Enums\Attendance\LeaveBalancePriorityMode;
use App\Models\AttendancePolicy;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveBalanceResolver
{
    /**
     * Deduct leave days using policy priority order.
     *
     * @return array<string, float> leave code => days consumed
     */
    public function deductLeaveDays(
        Employee $employee,
        AttendancePolicy $policy,
        float $days,
        int $year,
    ): array {
        if ($days <= 0) {
            return [];
        }

        $policy->loadMissing(['policyLeaveTypes.leaveType']);
        $orderedLeaveTypes = $policy->policyLeaveTypes
            ->sortBy('deduction_priority')
            ->values();

        $remaining = $days;
        $consumed = [];

        foreach ($orderedLeaveTypes as $policyLeaveType) {
            if ($remaining <= 0) {
                break;
            }

            $leaveType = $policyLeaveType->leaveType;
            if (! $leaveType) {
                continue;
            }

            $balance = $this->getOrCreateBalance($employee, $leaveType, $year);
            $available = max(0, (float) $balance->closing_balance);
            $toDeduct = min($remaining, $available);

            if ($toDeduct > 0) {
                $code = strtoupper($leaveType->code);
                $consumed[$code] = ($consumed[$code] ?? 0) + $toDeduct;
                $this->applyConsumption($balance, $toDeduct);
                $remaining -= $toDeduct;
            }
        }

        if ($remaining > 0) {
            if ($policy->leave_balance_priority_mode === LeaveBalancePriorityMode::STRICT_LWP_FALLBACK
                || ! $policy->allow_negative_leave_balance) {
                $lwpType = LeaveType::where('company_id', $employee->company_id)
                    ->where('code', 'LWP')
                    ->first();

                if ($lwpType) {
                    $consumed['LWP'] = ($consumed['LWP'] ?? 0) + $remaining;
                    $balance = $this->getOrCreateBalance($employee, $lwpType, $year);
                    $this->applyConsumption($balance, $remaining);
                } elseif (! $policy->allow_negative_leave_balance) {
                    throw ValidationException::withMessages([
                        'leave_balance' => "Insufficient leave balance for employee {$employee->employee_code}.",
                    ]);
                }
            } else {
                $firstType = $orderedLeaveTypes->first()?->leaveType;
                if ($firstType) {
                    $code = strtoupper($firstType->code);
                    $consumed[$code] = ($consumed[$code] ?? 0) + $remaining;
                    $balance = $this->getOrCreateBalance($employee, $firstType, $year);
                    $this->applyConsumption($balance, $remaining);
                }
            }
        }

        return $consumed;
    }

    /**
     * Validate leave can be deducted without persisting changes.
     *
     * @param  array<string, float>  $leaveDaysByCode
     */
    public function assertSufficientBalance(
        Employee $employee,
        AttendancePolicy $policy,
        array $leaveDaysByCode,
        int $year,
    ): void {
        foreach ($leaveDaysByCode as $code => $days) {
            if ($days <= 0) {
                continue;
            }

            $leaveType = LeaveType::where('company_id', $employee->company_id)
                ->where('code', strtoupper($code))
                ->first();

            if (! $leaveType) {
                continue;
            }

            $balance = $this->getOrCreateBalance($employee, $leaveType, $year);
            $available = (float) $balance->closing_balance;

            if (! $leaveType->is_paid || strtoupper($code) === 'LWP') {
                continue;
            }

            if ($days > $available && ! $policy->allow_negative_leave_balance) {
                throw ValidationException::withMessages([
                    'leave_balance' => "Insufficient {$code} balance for employee {$employee->employee_code}. Requested {$days}, available {$available}.",
                ]);
            }
        }
    }

    /**
     * Rebuild consumed/closing balances from daily attendance for a year (idempotent).
     */
    public function rebuildBalancesFromDailyRecords(Employee $employee, int $year): void
    {
        $policy = app(AttendancePolicyResolver::class)->resolveForEmployee($employee);
        if (! $policy) {
            return;
        }

        $this->initializeBalancesForEmployee($employee, $policy, $year);

        $records = EmployeeAttendance::query()
            ->where('employee_id', $employee->id)
            ->whereYear('attendance_date', $year)
            ->whereIn('attendance_status', ['leave', 'half_day'])
            ->with('leaveType')
            ->get();

        $byCode = [];
        foreach ($records as $record) {
            if ($record->attendance_status->value === 'leave') {
                $code = strtoupper($record->leaveType?->code ?? 'LWP');
                $byCode[$code] = ($byCode[$code] ?? 0) + 1.0;
            } elseif ($record->attendance_status->value === 'half_day') {
                $code = strtoupper($record->leaveType?->code ?? 'LWP');
                $byCode[$code] = ($byCode[$code] ?? 0) + 0.5;
            }
        }

        $leaveTypes = LeaveType::where('company_id', $employee->company_id)->get();
        foreach ($leaveTypes as $leaveType) {
            $balance = $this->getOrCreateBalance($employee, $leaveType, $year);
            $consumed = $byCode[strtoupper($leaveType->code)] ?? 0.0;
            $balance->consumed = $consumed;
            $balance->closing_balance = max(
                0,
                (float) $balance->opening_balance + (float) $balance->accrued - $consumed
            );
            $balance->save();
        }
    }

    public function initializeBalancesForEmployee(Employee $employee, AttendancePolicy $policy, int $year): void
    {
        $policy->loadMissing('policyLeaveTypes.leaveType');

        $quotasByLeaveTypeId = [];
        foreach ($policy->policyLeaveTypes as $policyLeaveType) {
            if ($policyLeaveType->leaveType) {
                $quotasByLeaveTypeId[$policyLeaveType->leave_type_id] = (float) $policyLeaveType->annual_quota;
            }
        }

        $leaveTypes = LeaveType::where('company_id', $employee->company_id)->where('is_active', true)->get();
        foreach ($leaveTypes as $leaveType) {
            $opening = $quotasByLeaveTypeId[$leaveType->id] ?? (
                $leaveType->annual_quota !== null ? (float) $leaveType->annual_quota : 0.0
            );
            $this->getOrCreateBalance($employee, $leaveType, $year, $opening);
        }
    }

    public function getOrCreateBalance(
        Employee $employee,
        LeaveType $leaveType,
        int $year,
        ?float $opening = null,
    ): EmployeeLeaveBalance {
        return EmployeeLeaveBalance::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
            ],
            [
                'company_id' => $employee->company_id,
                'opening_balance' => $opening ?? 0,
                'accrued' => 0,
                'consumed' => 0,
                'closing_balance' => $opening ?? 0,
            ]
        );
    }

    private function applyConsumption(EmployeeLeaveBalance $balance, float $days): void
    {
        DB::transaction(function () use ($balance, $days) {
            $balance->refresh();
            $balance->consumed = (float) $balance->consumed + $days;
            $balance->closing_balance = max(
                0,
                (float) $balance->opening_balance + (float) $balance->accrued - (float) $balance->consumed
            );
            $balance->save();
        });
    }
}
