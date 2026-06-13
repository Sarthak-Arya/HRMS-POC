<?php

namespace App\Services\Compensation;

use App\Enums\Compensation\CompensationScopeType;
use App\Models\CompensationOverride;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CompensationOverrideService
{
    /**
     * @return Collection<int, CompensationOverride>
     */
    public function listForScope(int $companyId, CompensationScopeType $scopeType, ?int $scopeId): Collection
    {
        return CompensationOverride::with('component')
            ->where('company_id', $companyId)
            ->where('scope_type', $scopeType->value)
            ->when(
                $scopeType === CompensationScopeType::COMPANY,
                fn ($q) => $q->whereNull('scope_id'),
                fn ($q) => $q->where('scope_id', $scopeId),
            )
            ->orderByDesc('effective_from')
            ->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $companyId, array $data): CompensationOverride
    {
        $validated = $this->validate($data);
        $scopeType = CompensationScopeType::from($validated['scope_type']);
        $scopeId = $validated['scope_id'] ?? null;

        $this->assertScopeBelongsToCompany($companyId, $scopeType, $scopeId);

        return CompensationOverride::create([
            ...$validated,
            'company_id' => $companyId,
            'scope_id' => $scopeType === CompensationScopeType::COMPANY ? null : $scopeId,
            'created_by' => Auth::id(),
        ]);
    }

    public function delete(int $companyId, int $overrideId): void
    {
        CompensationOverride::where('company_id', $companyId)->findOrFail($overrideId)->delete();
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

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        $validator = Validator::make($data, [
            'scope_type' => 'required|in:company,location,department,employee',
            'scope_id' => 'nullable|integer',
            'component_id' => 'required|exists:compensation_components,id',
            'override_type' => 'required|in:REPLACE,ADD,REMOVE',
            'value' => 'nullable|numeric|min:0',
            'calculation_type' => 'nullable|in:FIXED,PERCENT_BASIC,PERCENT_CTC,FORMULA',
            'formula_expression' => 'nullable|string',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        if ($validated['override_type'] !== 'REMOVE' && !isset($validated['value']) && !isset($validated['calculation_type'])) {
            throw ValidationException::withMessages([
                'value' => 'Value or calculation type is required unless override type is REMOVE.',
            ]);
        }

        return $validated;
    }
}
