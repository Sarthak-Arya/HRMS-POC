<?php

namespace App\Services\Compensation;

use App\Models\Employee;
use App\Models\EmployeeCompensationHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EmployeeCompensationService
{
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
        Employee::where('company_id', $companyId)->findOrFail($employeeId);

        $validated = Validator::make($data, [
            'structure_id' => 'required|exists:compensation_structures,id',
            'annual_ctc' => 'required|numeric|min:0',
            'monthly_gross' => 'nullable|numeric|min:0',
            'effective_from' => 'required|date',
            'revision_reason' => 'nullable|string|max:255',
        ])->validate();

        $monthlyGross = $validated['monthly_gross'] ?? round($validated['annual_ctc'] / 12, 2);
        $effectiveFrom = Carbon::parse($validated['effective_from']);

        return DB::transaction(function () use ($companyId, $employeeId, $validated, $monthlyGross, $effectiveFrom) {
            EmployeeCompensationHistory::where('employee_id', $employeeId)
                ->whereNull('effective_to')
                ->where('effective_from', '<', $effectiveFrom)
                ->update(['effective_to' => $effectiveFrom->copy()->subDay()->toDateString()]);

            return EmployeeCompensationHistory::create([
                'company_id' => $companyId,
                'employee_id' => $employeeId,
                'structure_id' => $validated['structure_id'],
                'annual_ctc' => $validated['annual_ctc'],
                'monthly_gross' => $monthlyGross,
                'effective_from' => $validated['effective_from'],
                'effective_to' => null,
                'revision_reason' => $validated['revision_reason'] ?? null,
                'approved_by' => Auth::id(),
            ]);
        });
    }

    public function resolvePreview(int $companyId, int $employeeId): ResolvedCompensation
    {
        $employee = Employee::where('company_id', $companyId)->findOrFail($employeeId);

        return app(CompensationResolver::class)->resolveForEmployee($employee);
    }
}
