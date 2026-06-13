<?php

namespace App\Services\Compensation;

use App\Models\CompensationStructure;
use App\Models\StructureComponent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Service class to manage compensation structures and their components.
 * Handles listing, creating, updating, and deleting structures, as well as validation and component synchronization.
 */
class CompensationStructureService
{
    /**
     * List all compensation structures for a specific company.
     *
     * @param int $companyId The ID of the company.
     * @return Collection<int, CompensationStructure> A collection of compensation structures.
     */
    public function listForCompany(int $companyId): Collection
    {
        return CompensationStructure::withCount('structureComponents')
            ->where('company_id', $companyId)
            ->orderByDesc('is_default')
            ->orderBy('structure_name')
            ->get();
    }

    /**
     * Create a new compensation structure with its components.
     *
     * @param int $companyId The ID of the company.
     * @param array<string, mixed> $data The structure data.
     * @param list<array<string, mixed>> $components The components to be attached to the structure.
     * @return CompensationStructure The newly created compensation structure.
     * @throws ValidationException If validation fails.
     */
    public function create(int $companyId, array $data, array $components): CompensationStructure
    {
        $validated = $this->validateStructure($companyId, $data);
        $this->validateComponents($components);

        return DB::transaction(function () use ($companyId, $validated, $components) {
            if (!empty($validated['is_default'])) {
                $this->clearDefaultFlag($companyId);
            }

            $structure = CompensationStructure::create([
                ...$validated,
                'company_id' => $companyId,
            ]);

            $this->syncComponents($structure, $components);

            return $structure->load('structureComponents.component');
        });
    }

    /**
     * Update an existing compensation structure and its components.
     *
     * @param int $companyId The ID of the company.
     * @param int $structureId The ID of the structure to update.
     * @param array<string, mixed> $data The updated structure data.
     * @param list<array<string, mixed>> $components The updated components list.
     * @return CompensationStructure The updated compensation structure.
     * @throws ValidationException If validation fails.
     */
    public function update(int $companyId, int $structureId, array $data, array $components): CompensationStructure
    {
        $structure = CompensationStructure::where('company_id', $companyId)->findOrFail($structureId);
        $validated = $this->validateStructure($companyId, $data, $structureId);
        $this->validateComponents($components);

        return DB::transaction(function () use ($companyId, $structure, $validated, $components) {
            if (!empty($validated['is_default'])) {
                $this->clearDefaultFlag($companyId, $structure->id);
            }

            $structure->update($validated);
            $this->syncComponents($structure, $components);

            return $structure->fresh(['structureComponents.component']);
        });
    }

    /**
     * Delete a compensation structure.
     *
     * @param int $companyId The ID of the company.
     * @param int $structureId The ID of the structure to delete.
     * @return void
     */
    public function delete(int $companyId, int $structureId): void
    {
        $structure = CompensationStructure::where('company_id', $companyId)->findOrFail($structureId);
        $structure->delete();
    }

    /**
     * Sync components to a compensation structure.
     *
     * @param CompensationStructure $structure The compensation structure model.
     * @param list<array<string, mixed>> $components The list of components to sync.
     * @return void
     */
    private function syncComponents(CompensationStructure $structure, array $components): void
    {
        $structure->structureComponents()->delete();

        foreach ($components as $index => $row) {
            StructureComponent::create([
                'structure_id' => $structure->id,
                'component_id' => $row['component_id'],
                'value' => $row['value'] ?? null,
                'calculation_type' => $row['calculation_type'] ?? null,
                'formula_expression' => $row['formula_expression'] ?? null,
                'is_mandatory' => (bool) ($row['is_mandatory'] ?? false),
                'display_order' => $row['display_order'] ?? ($index + 1),
            ]);
        }
    }

    /**
     * Clear the default flag for all structures of a company.
     *
     * @param int $companyId The ID of the company.
     * @param int|null $exceptId Optional structure ID to exclude from clearing.
     * @return void
     */
    private function clearDefaultFlag(int $companyId, ?int $exceptId = null): void
    {
        CompensationStructure::where('company_id', $companyId)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->update(['is_default' => false]);
    }

    /**
     * Validate the compensation structure data.
     *
     * @param int $companyId The ID of the company.
     * @param array<string, mixed> $data The data to validate.
     * @param int|null $ignoreId Optional structure ID to ignore during unique validation.
     * @return array<string, mixed> The validated data.
     * @throws ValidationException If validation fails.
     */
    private function validateStructure(int $companyId, array $data, ?int $ignoreId = null): array
    {
        $validator = Validator::make($data, [
            'structure_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('compensation_structures', 'structure_name')
                    ->where('company_id', $companyId)
                    ->ignore($ignoreId),
            ],
            'description' => 'nullable|string',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate the components data.
     *
     * @param list<array<string, mixed>> $components The list of components to validate.
     * @return void
     * @throws ValidationException If validation fails or duplicate components are found.
     */
    private function validateComponents(array $components): void
    {
        $validator = Validator::make(['components' => $components], [
            'components' => 'required|array|min:1',
            'components.*.component_id' => 'required|exists:compensation_components,id',
            'components.*.value' => 'nullable|numeric|min:0',
            'components.*.calculation_type' => 'nullable|in:FIXED,PERCENT_BASIC,PERCENT_CTC,FORMULA',
            'components.*.formula_expression' => 'nullable|string',
            'components.*.is_mandatory' => 'boolean',
            'components.*.display_order' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $ids = array_column($components, 'component_id');
        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'components' => 'Duplicate components are not allowed in a structure.',
            ]);
        }
    }
}

