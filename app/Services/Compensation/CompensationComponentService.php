<?php

namespace App\Services\Compensation;

use App\Models\CompensationComponent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CompensationComponentService
{
    /**
     * @return Collection<int, CompensationComponent>
     */
    public function listForCompany(int $companyId, bool $activeOnly = false): Collection
    {
        return CompensationComponent::where('company_id', $companyId)
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('display_order')
            ->orderBy('component_name')
            ->get();
    }

    /**
     * Salary structure components only (excludes payroll adjustment types).
     *
     * @return Collection<int, CompensationComponent>
     */
    public function listStructureComponents(int $companyId, bool $activeOnly = true): Collection
    {
        return CompensationComponent::where('company_id', $companyId)
            ->where('is_payroll_adjustment', false)
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('display_order')
            ->orderBy('component_name')
            ->get();
    }

    /**
     * @return Collection<int, CompensationComponent>
     */
    public function listPayrollAdjustments(int $companyId): Collection
    {
        return CompensationComponent::where('company_id', $companyId)
            ->where('is_payroll_adjustment', true)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('component_name')
            ->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $companyId, array $data): CompensationComponent
    {
        $validated = $this->validate($companyId, $data);

        return CompensationComponent::create([
            ...$validated,
            'company_id' => $companyId,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $companyId, int $componentId, array $data): CompensationComponent
    {
        $component = CompensationComponent::where('company_id', $companyId)->findOrFail($componentId);
        $validated = $this->validate($companyId, $data, $componentId);
        $component->update($validated);

        return $component->fresh();
    }

    public function deactivate(int $companyId, int $componentId): CompensationComponent
    {
        $component = CompensationComponent::where('company_id', $companyId)->findOrFail($componentId);
        $component->update(['is_active' => false]);

        return $component->fresh();
    }

    /**
     * @param list<int> $orderedIds
     */
    public function reorder(int $companyId, array $orderedIds): void
    {
        foreach ($orderedIds as $order => $id) {
            CompensationComponent::where('company_id', $companyId)
                ->where('id', $id)
                ->update(['display_order' => $order + 1]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validate(int $companyId, array $data, ?int $ignoreId = null): array
    {
        $validator = Validator::make($data, [
            'component_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('compensation_components', 'component_name')
                    ->where('company_id', $companyId)
                    ->ignore($ignoreId),
            ],
            'component_type' => 'required|in:EARNING,DEDUCTION,BENEFIT',
            'default_calculation_type' => 'required|in:FIXED,PERCENT_BASIC,PERCENT_CTC,FORMULA',
            'default_value' => 'nullable|numeric|min:0',
            'statutory_component' => 'nullable|in:PF,ESIC,PT,LWF,TDS',
            'benefit_plan' => 'nullable|in:NPS,80C,80D,80DD,80DDB,80GGC,OTHER',
            'associate_investment' => 'nullable|in:NPS,80C,80D,80DD,80DDB,80GGC,OTHER',
            'include_employer_contribution' => 'boolean',
            'is_superannuation' => 'boolean',
            'pro_rata_basis' => 'boolean',
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
            'is_payroll_adjustment' => 'boolean',
            'included_in_pf_wages' => 'boolean',
            'included_in_esi_wages' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        if (!empty($validated['is_payroll_adjustment'])) {
            $validated['default_calculation_type'] = 'FIXED';
            $validated['default_value'] = null;
            $validated['statutory_component'] = null;
            $validated['benefit_plan'] = null;
            $validated['associate_investment'] = null;
            $validated['include_employer_contribution'] = false;
            $validated['is_superannuation'] = false;
            $validated['pro_rata_basis'] = false;
            $validated['included_in_pf_wages'] = false;
            $validated['included_in_esi_wages'] = false;
        }

        if (($validated['component_type'] ?? '') !== 'EARNING') {
            $validated['included_in_pf_wages'] = false;
            $validated['included_in_esi_wages'] = false;
        }

        if (($validated['component_type'] ?? '') !== 'BENEFIT') {
            $validated['benefit_plan'] = null;
            $validated['associate_investment'] = null;
            $validated['include_employer_contribution'] = false;
            $validated['is_superannuation'] = false;
            $validated['pro_rata_basis'] = false;
        } else {
            $validated['default_calculation_type'] = 'FIXED';
            $validated['statutory_component'] = null;
            $validated['included_in_pf_wages'] = false;
            $validated['included_in_esi_wages'] = false;
        }

        return $validated;
    }
}
