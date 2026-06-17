<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeeCompensationHistory;
use App\Models\MonthlyAttendance;
use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayrollReadinessService
{
    /**
     * @return array{
     *     total_employees: int,
     *     ready_count: int,
     *     missing_attendance: Collection,
     *     missing_compensation: Collection,
     *     is_ready: bool
     * }
     */
    public function assess(PayrollRun $run, ?Collection $employees = null): array
    {
        $employees ??= $this->eligibleEmployees($run);
        $asOf = Carbon::create($run->year, $run->month)->endOfMonth();

        $missingAttendance = collect();
        $missingCompensation = collect();

        foreach ($employees as $employee) {
            $hasAttendance = MonthlyAttendance::query()
                ->where('employee_id', $employee->id)
                ->where('company_id', $run->company_id)
                ->where('month', $run->month)
                ->where('year', $run->year)
                ->exists();

            if (! $hasAttendance) {
                $missingAttendance->push($employee);
            }

            $hasCompensation = EmployeeCompensationHistory::query()
                ->where('employee_id', $employee->id)
                ->where('effective_from', '<=', $asOf)
                ->where(function ($query) use ($asOf) {
                    $query->whereNull('effective_to')->orWhere('effective_to', '>=', $asOf);
                })
                ->exists();

            if (! $hasCompensation) {
                $missingCompensation->push($employee);
            }
        }

        $blockedIds = $missingAttendance->pluck('id')->merge($missingCompensation->pluck('id'))->unique();
        $readyCount = $employees->count() - $blockedIds->count();

        return [
            'total_employees' => $employees->count(),
            'ready_count' => max(0, $readyCount),
            'missing_attendance' => $missingAttendance,
            'missing_compensation' => $missingCompensation,
            'is_ready' => $missingAttendance->isEmpty() && $missingCompensation->isEmpty(),
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
