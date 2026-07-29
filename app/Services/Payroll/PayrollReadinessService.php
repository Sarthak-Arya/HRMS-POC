<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeeCompensationHistory;
use App\Models\MonthlyAttendance;
use App\Models\PayrollRun;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\MonthLockAndReconciliationService;
use App\Services\Compensation\CompensationResolver;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayrollReadinessService
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly MonthLockAndReconciliationService $lockService,
        private readonly CompensationResolver $compensationResolver,
    ) {}

    /**
     * @return array{
     *     total_employees: int,
     *     ready_count: int,
     *     missing_attendance: Collection,
     *     missing_compensation: Collection,
     *     unlocked_summaries: Collection,
     *     reconciliation_conflicts: list<array{employee_id: int, employee_code: string|null, message: string}>,
     *     governance_messages: list<string>,
     *     is_ready: bool
     * }
     */
    public function assess(PayrollRun $run, ?Collection $employees = null): array
    {
        $employees ??= $this->eligibleEmployees($run);
        $asOf = Carbon::create($run->year, $run->month)->endOfMonth();

        $missingAttendance = collect();
        $missingCompensation = collect();
        $unlockedSummaries = collect();

        foreach ($employees as $employee) {
            $summary = MonthlyAttendance::query()
                ->where('employee_id', $employee->id)
                ->where('company_id', $run->company_id)
                ->where('month', $run->month)
                ->where('year', $run->year)
                ->first();

            if (! $summary) {
                $missingAttendance->push($employee);
            } elseif (! $this->attendanceService->isSummaryLocked($summary)) {
                $unlockedSummaries->push($employee);
            }

            $hasCompensation = EmployeeCompensationHistory::query()
                ->where('employee_id', $employee->id)
                ->where('effective_from', '<=', $asOf)
                ->where(function ($query) use ($asOf) {
                    $query->whereNull('effective_to')->orWhere('effective_to', '>=', $asOf);
                })
                ->exists();

            if (! $hasCompensation) {
                $resolved = $this->compensationResolver->resolveForEmployee($employee, $asOf);
                $hasCompensation = $resolved->structureId !== null && $resolved->lines->isNotEmpty();
            }

            if (! $hasCompensation) {
                $missingCompensation->push($employee);
            }
        }

        $governance = $this->lockService->assertReadyForPayroll($run);
        $blockedIds = $missingAttendance->pluck('id')
            ->merge($missingCompensation->pluck('id'))
            ->merge($unlockedSummaries->pluck('id'))
            ->merge(collect($governance['reconciliation_conflicts'])->pluck('employee_id'))
            ->unique();
        $readyCount = $employees->count() - $blockedIds->count();

        return [
            'total_employees' => $employees->count(),
            'ready_count' => max(0, $readyCount),
            'missing_attendance' => $missingAttendance,
            'missing_compensation' => $missingCompensation,
            'unlocked_summaries' => $unlockedSummaries,
            'reconciliation_conflicts' => $governance['reconciliation_conflicts'],
            'governance_messages' => $governance['messages'],
            'is_ready' => $missingAttendance->isEmpty()
                && $missingCompensation->isEmpty()
                && $unlockedSummaries->isEmpty()
                && $governance['reconciliation_conflicts'] === [],
        ];
    }

    public function eligibleEmployees(PayrollRun $run, ?int $departmentId = null, ?int $designationId = null): Collection
    {
        $periodEnd = Carbon::create($run->year, $run->month)->endOfMonth();

        return Employee::query()
            ->where('company_id', $run->company_id)
            ->where(function ($query) use ($periodEnd) {
                $query->whereNull('dol')->orWhere('dol', '>=', $periodEnd->copy()->startOfMonth());
            })
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($designationId, fn ($q) => $q->where('designation_id', $designationId))
            ->orderBy('employee_code')
            ->get();
    }
}
