<?php

namespace App\Services\Compensation;

use App\Models\Employee;
use App\Models\EmployeeCompensationHistory;
use App\Services\Observability\DomainTelemetry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EmployeeCompensationService
{
    public function __construct(
        private readonly DomainTelemetry $telemetry,
    ) {
    }

    /**
     * @return Collection<int, EmployeeCompensationHistory>
     */
    public function historyForEmployee(int $companyId, int $employeeId): Collection
    {
        return EmployeeCompensationHistory::with(['structure', 'approvedBy'])
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->orderByDesc('effective_from')
            ->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function assignRevision(int $companyId, int $employeeId, array $data): EmployeeCompensationHistory
    {
        $employee = Employee::where('company_id', $companyId)->findOrFail($employeeId);

        $validated = Validator::make($data, [
            'structure_id' => 'required|exists:compensation_structures,id',
            'effective_from' => 'required|date',
            'revision_reason' => 'nullable|string|max:255',
        ])->validate();

        $effectiveFrom = Carbon::parse($validated['effective_from']);
        $resolved = app(CompensationResolver::class)->resolveForEmployee(
            $employee,
            $effectiveFrom,
            (int) $validated['structure_id'],
        );

        if ($resolved->lines->isEmpty()) {
            throw ValidationException::withMessages([
                'structure_id' => 'The selected structure has no active components to calculate compensation.',
            ]);
        }

        $history = DB::transaction(function () use ($companyId, $employeeId, $validated, $resolved, $effectiveFrom) {
            EmployeeCompensationHistory::where('employee_id', $employeeId)
                ->whereNull('effective_to')
                ->where('effective_from', '<', $effectiveFrom)
                ->update(['effective_to' => $effectiveFrom->copy()->subDay()->toDateString()]);

            return EmployeeCompensationHistory::create([
                'company_id' => $companyId,
                'employee_id' => $employeeId,
                'structure_id' => $validated['structure_id'],
                'annual_ctc' => $resolved->annualCtc ?? 0,
                'monthly_gross' => $resolved->monthlyGross ?? 0,
                'effective_from' => $validated['effective_from'],
                'effective_to' => null,
                'revision_reason' => $validated['revision_reason'] ?? null,
                'approved_by' => Auth::id(),
            ]);
        });

        $this->telemetry->emit('compensation.assignment.changed', 'audit', 'success', [
            'company.id' => $companyId,
        ]);

        return $history;
    }

    public function resolvePreview(int $companyId, int $employeeId, ?int $structureId = null): ResolvedCompensation
    {
        $employee = Employee::where('company_id', $companyId)->findOrFail($employeeId);

        return app(CompensationResolver::class)->resolveForEmployee(
            $employee,
            null,
            $structureId,
        );
    }

    public function resolveOrProvisionForPayroll(
        Employee $employee,
        Carbon $asOf,
        ResolvedCompensation $resolved,
    ): EmployeeCompensationHistory {
        $history = EmployeeCompensationHistory::query()
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $asOf)
            ->where(function ($query) use ($asOf) {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $asOf);
            })
            ->orderByDesc('effective_from')
            ->first();

        if ($history && (int) $history->structure_id === (int) $resolved->structureId) {
            return $history;
        }

        if ($resolved->structureId === null || $resolved->lines->isEmpty()) {
            throw ValidationException::withMessages([
                'compensation' => 'Employee has no active compensation for this payroll period.',
            ]);
        }

        $effectiveFrom = $asOf->copy()->startOfMonth();
        if ($employee->doj && $employee->doj->gt($effectiveFrom)) {
            $effectiveFrom = $employee->doj->copy();
        }

        return DB::transaction(function () use ($employee, $history, $resolved, $effectiveFrom) {
            if ($history) {
                $closeOn = $effectiveFrom->copy()->subDay();
                if ($closeOn->gte(Carbon::parse($history->effective_from))) {
                    $history->update(['effective_to' => $closeOn->toDateString()]);
                }
            }

            return EmployeeCompensationHistory::create([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'structure_id' => $resolved->structureId,
                'annual_ctc' => $resolved->annualCtc ?? 0,
                'monthly_gross' => $resolved->monthlyGross ?? 0,
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_to' => null,
                'revision_reason' => $history
                    ? 'Aligned to '.$resolved->structureSource.' during payroll'
                    : 'Auto-provisioned from '.$resolved->structureSource.' during payroll',
                'approved_by' => Auth::id(),
            ]);
        });
    }
}
