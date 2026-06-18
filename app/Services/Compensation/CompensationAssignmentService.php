<?php

namespace App\Services\Compensation;

use App\Enums\Compensation\CompensationScopeType;
use App\Models\CompensationStructureAssignment;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CompensationAssignmentService
{
    /**
     * @return Collection<int, CompensationStructureAssignment>
     */
    public function listForCompany(int $companyId, ?CompensationScopeType $scopeType = null): Collection
    {
        return CompensationStructureAssignment::with('structure')
            ->where('company_id', $companyId)
            ->when($scopeType, fn ($q) => $q->where('scope_type', $scopeType->value))
            ->orderByDesc('effective_from')
            ->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function assign(int $companyId, array $data): CompensationStructureAssignment
    {
        $validated = $this->validate($data);
        $scopeType = CompensationScopeType::from($validated['scope_type']);
        $scopeId = $validated['scope_id'] ?? null;

        $this->assertScopeBelongsToCompany($companyId, $scopeType, $scopeId);
        $this->assertNoOverlap($companyId, $scopeType, $scopeId, $validated['effective_from'], $validated['effective_to'] ?? null);

        $this->closeOpenAssignments($companyId, $scopeType, $scopeId, Carbon::parse($validated['effective_from']));

        return CompensationStructureAssignment::create([
            'company_id' => $companyId,
            'scope_type' => $scopeType->value,
            'scope_id' => $scopeType === CompensationScopeType::COMPANY ? null : $scopeId,
            'structure_id' => $validated['structure_id'],
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'] ?? null,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @param list<int> $scopeIds
     * @return array{created: Collection<int, CompensationStructureAssignment>, failed: list<array{scope_id: int|null, message: string}>}
     */
    public function assignBulk(int $companyId, array $data, array $scopeIds): array
    {
        $scopeType = CompensationScopeType::from($data['scope_type']);
        $created = collect();
        $failed = [];

        if ($scopeType === CompensationScopeType::COMPANY) {
            try {
                $created->push($this->assign($companyId, array_merge($data, ['scope_id' => null])));
            } catch (ValidationException $e) {
                $failed[] = [
                    'scope_id' => null,
                    'message' => (string) collect($e->errors())->flatten()->first(),
                ];
            }

            return ['created' => $created, 'failed' => $failed];
        }

        foreach ($scopeIds as $scopeId) {
            try {
                $created->push($this->assign($companyId, array_merge($data, ['scope_id' => (int) $scopeId])));
            } catch (ValidationException $e) {
                $failed[] = [
                    'scope_id' => (int) $scopeId,
                    'message' => (string) collect($e->errors())->flatten()->first(),
                ];
            }
        }

        return ['created' => $created, 'failed' => $failed];
    }

    public function delete(int $companyId, int $assignmentId): void
    {
        CompensationStructureAssignment::where('company_id', $companyId)->findOrFail($assignmentId)->delete();
    }

    /**
     * @return array<string, ?array{structure_name: string, source: string}>
     */
    public function inheritanceChainForEmployee(Employee $employee, ?Carbon $asOf = null): array
    {
        return app(CompensationResolver::class)->structureInheritanceChain($employee, $asOf);
    }

    private function assertScopeBelongsToCompany(int $companyId, CompensationScopeType $scopeType, ?int $scopeId): void
    {
        if ($scopeType === CompensationScopeType::COMPANY) {
            return;
        }

        if (!$scopeId) {
            throw ValidationException::withMessages(['scope_id' => 'Scope ID is required for this scope type.']);
        }

        $valid = match ($scopeType) {
            CompensationScopeType::LOCATION => \App\Models\Location::where('company_id', $companyId)->where('id', $scopeId)->exists(),
            CompensationScopeType::DEPARTMENT => \App\Models\Department::where('company_id', $companyId)->where('id', $scopeId)->exists(),
            CompensationScopeType::EMPLOYEE => Employee::where('company_id', $companyId)->where('id', $scopeId)->exists(),
            default => false,
        };

        if (!$valid) {
            throw ValidationException::withMessages(['scope_id' => 'The selected scope does not belong to this company.']);
        }
    }

    private function assertNoOverlap(
        int $companyId,
        CompensationScopeType $scopeType,
        ?int $scopeId,
        string $effectiveFrom,
        ?string $effectiveTo,
    ): void {
        $from = Carbon::parse($effectiveFrom);
        $to = $effectiveTo ? Carbon::parse($effectiveTo) : null;

        $overlap = CompensationStructureAssignment::where('company_id', $companyId)
            ->where('scope_type', $scopeType->value)
            ->when(
                $scopeType === CompensationScopeType::COMPANY,
                fn ($q) => $q->whereNull('scope_id'),
                fn ($q) => $q->where('scope_id', $scopeId),
            )
            ->where(function ($query) use ($from, $to) {
                $query->where(function ($q) use ($from) {
                    $q->whereNull('effective_to')->orWhere('effective_to', '>=', $from);
                });
                if ($to) {
                    $query->where('effective_from', '<=', $to);
                }
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'effective_from' => 'An overlapping assignment already exists for this scope.',
            ]);
        }
    }

    private function closeOpenAssignments(
        int $companyId,
        CompensationScopeType $scopeType,
        ?int $scopeId,
        Carbon $newEffectiveFrom,
    ): void {
        CompensationStructureAssignment::where('company_id', $companyId)
            ->where('scope_type', $scopeType->value)
            ->when(
                $scopeType === CompensationScopeType::COMPANY,
                fn ($q) => $q->whereNull('scope_id'),
                fn ($q) => $q->where('scope_id', $scopeId),
            )
            ->whereNull('effective_to')
            ->where('effective_from', '<', $newEffectiveFrom)
            ->update(['effective_to' => $newEffectiveFrom->copy()->subDay()->toDateString()]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        $validator = Validator::make($data, [
            'scope_type' => 'required|in:company,location,department,employee',
            'scope_id' => 'nullable|integer',
            'structure_id' => 'required|exists:compensation_structures,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
