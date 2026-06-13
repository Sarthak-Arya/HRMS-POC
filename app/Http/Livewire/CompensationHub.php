<?php

namespace App\Http\Livewire;

use App\Enums\Compensation\CompensationScopeType;
use App\Models\CompensationStructure;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Services\Compensation\CompensationAssignmentService;
use App\Services\Compensation\CompensationComponentService;
use App\Services\Compensation\CompensationOverrideService;
use App\Services\Compensation\CompensationResolver;
use App\Services\Compensation\CompensationStructureService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Livewire component for managing compensation-related configurations.
 * This hub handles compensation components, structures, assignments, and overrides.
 */
class CompensationHub extends Component
{
    /** @var string The ID of the current company. */
    public string $companyId = '';

    /** @var string The currently active tab in the UI. */
    public string $activeTab = 'components';

    // Components tab
    /** @var bool Whether to show the component creation/edit modal. */
    public bool $showComponentModal = false;

    /** @var int|null The ID of the component being edited. */
    public ?int $editingComponentId = null;

    /** @var string The name of the component. */
    public string $componentName = '';

    /** @var string The type of the component (e.g., EARNING, DEDUCTION). */
    public string $componentType = 'EARNING';

    /** @var string The default calculation type for the component. */
    public string $defaultCalculationType = 'FIXED';

    /** @var mixed The default value of the component. */
    public $defaultValue = '';

    /** @var string The statutory component type, if applicable. */
    public string $statutoryComponent = '';

    /** @var bool Whether the component is taxable. */
    public bool $isTaxable = true;

    /** @var bool Whether the component is active. */
    public bool $componentIsActive = true;

    /** @var int The display order of the component. */
    public int $componentDisplayOrder = 0;

    // Structures tab
    /** @var bool Whether to show the structure creation/edit modal. */
    public bool $showStructureModal = false;

    /** @var int|null The ID of the structure being edited. */
    public ?int $editingStructureId = null;

    /** @var string The name of the compensation structure. */
    public string $structureName = '';

    /** @var string The description of the compensation structure. */
    public string $structureDescription = '';

    /** @var string The effective from date of the structure. */
    public string $structureEffectiveFrom = '';

    /** @var string The effective to date of the structure. */
    public string $structureEffectiveTo = '';

    /** @var bool Whether the structure is active. */
    public bool $structureIsActive = true;

    /** @var bool Whether this is the default structure for the company. */
    public bool $structureIsDefault = false;

    /** @var list<array<string, mixed>> The components rows within the structure. */
    public array $structureRows = [];

    /** @var mixed Annual CTC used for previewing structure calculations. */
    public $previewAnnualCtc = 600000;

    // Assignments tab
    /** @var string The scope type for structure assignment (e.g., company, location, department, employee). */
    public string $assignmentScopeType = 'company';

    /** @var string The specific ID for the assignment scope. */
    public string $assignmentScopeId = '';

    /** @var string The ID of the structure being assigned. */
    public string $assignmentStructureId = '';

    /** @var string The effective from date for the assignment. */
    public string $assignmentEffectiveFrom = '';

    /** @var string The effective to date for the assignment. */
    public string $assignmentEffectiveTo = '';

    /** @var array Information about the inheritance chain for an employee's compensation. */
    public array $inheritanceChain = [];

    // Overrides tab
    /** @var string The scope type for the override. */
    public string $overrideScopeType = 'company';

    /** @var string The specific ID for the override scope. */
    public string $overrideScopeId = '';

    /** @var string The ID of the structure used for override preview. */
    public string $overrideStructureId = '';

    /** @var mixed Annual CTC used for override preview. */
    public $overridePreviewCtc = 600000;

    /** @var bool Whether to show the override creation modal. */
    public bool $showOverrideModal = false;

    /** @var string The ID of the component being overridden. */
    public string $overrideComponentId = '';

    /** @var string The type of override (e.g., REPLACE, ADD). */
    public string $overrideType = 'REPLACE';

    /** @var mixed The value for the override. */
    public $overrideValue = '';

    /** @var string The calculation type for the override. */
    public string $overrideCalculationType = 'FIXED';

    /** @var string The effective from date for the override. */
    public string $overrideEffectiveFrom = '';

    /**
     * Initialize the component.
     *
     * @param string|null $company_id The company ID from the route.
     * @return void
     */
    public function mount(?string $company_id = null): void
    {
        $this->companyId = $company_id ?? (string) session()->get('companyId', '');
        if ($this->companyId !== '') {
            session()->put('companyId', $this->companyId);
        }

        $this->assignmentEffectiveFrom = now()->toDateString();
        $this->overrideEffectiveFrom = now()->toDateString();
        $this->structureEffectiveFrom = now()->toDateString();
        $this->addStructureRow();
    }

    /**
     * Change the currently active tab.
     *
     * @param string $tab The name of the tab to switch to.
     * @return void
     */
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // --- Components ---

    /**
     * Open the component modal for creating or editing.
     *
     * @param int|null $componentId The ID of the component to edit, or null for creation.
     * @return void
     */
    public function openComponentModal(?int $componentId = null): void
    {
        $this->resetComponentForm();
        $this->editingComponentId = $componentId;

        if ($componentId) {
            $component = app(CompensationComponentService::class)
                ->listForCompany((int) $this->companyId)
                ->firstWhere('id', $componentId);

            if ($component) {
                $this->componentName = $component->component_name;
                $this->componentType = $component->component_type->value;
                $this->defaultCalculationType = $component->default_calculation_type->value;
                $this->defaultValue = $component->default_value;
                $this->statutoryComponent = $component->statutory_component?->value ?? '';
                $this->isTaxable = $component->is_taxable;
                $this->componentIsActive = $component->is_active;
                $this->componentDisplayOrder = $component->display_order;
            }
        }

        $this->showComponentModal = true;
    }

    /**
     * Save the component data (create or update).
     *
     * @return void
     */
    public function saveComponent(): void
    {
        $service = app(CompensationComponentService::class);
        $payload = [
            'component_name' => $this->componentName,
            'component_type' => $this->componentType,
            'default_calculation_type' => $this->defaultCalculationType,
            'default_value' => $this->defaultValue !== '' ? $this->defaultValue : null,
            'statutory_component' => $this->statutoryComponent !== '' ? $this->statutoryComponent : null,
            'is_taxable' => $this->isTaxable,
            'is_active' => $this->componentIsActive,
            'display_order' => $this->componentDisplayOrder,
        ];

        try {
            if ($this->editingComponentId) {
                $service->update((int) $this->companyId, $this->editingComponentId, $payload);
                session()->flash('success', 'Component updated successfully.');
            } else {
                $service->create((int) $this->companyId, $payload);
                session()->flash('success', 'Component created successfully.');
            }
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return;
        }

        $this->showComponentModal = false;
        $this->resetComponentForm();
    }

    /**
     * Deactivate a compensation component.
     *
     * @param int $componentId The ID of the component to deactivate.
     * @return void
     */
    public function deactivateComponent(int $componentId): void
    {
        app(CompensationComponentService::class)->deactivate((int) $this->companyId, $componentId);
        session()->flash('success', 'Component deactivated.');
    }

    /**
     * Reset the component form fields and errors.
     *
     * @return void
     */
    private function resetComponentForm(): void
    {
        $this->editingComponentId = null;
        $this->componentName = '';
        $this->componentType = 'EARNING';
        $this->defaultCalculationType = 'FIXED';
        $this->defaultValue = '';
        $this->statutoryComponent = '';
        $this->isTaxable = true;
        $this->componentIsActive = true;
        $this->componentDisplayOrder = 0;
        $this->resetErrorBag();
    }

    // --- Structures ---

    /**
     * Open the structure modal for creating or editing.
     *
     * @param int|null $structureId The ID of the structure to edit, or null for creation.
     * @return void
     */
    public function openStructureModal(?int $structureId = null): void
    {
        $this->resetStructureForm();
        $this->editingStructureId = $structureId;

        if ($structureId) {
            $structure = CompensationStructure::with('structureComponents')
                ->where('company_id', $this->companyId)
                ->findOrFail($structureId);

            $this->structureName = $structure->structure_name;
            $this->structureDescription = $structure->description ?? '';
            $this->structureEffectiveFrom = $structure->effective_from?->toDateString() ?? '';
            $this->structureEffectiveTo = $structure->effective_to?->toDateString() ?? '';
            $this->structureIsActive = $structure->is_active;
            $this->structureIsDefault = $structure->is_default;
            $this->structureRows = $structure->structureComponents->map(fn ($row) => [
                'component_id' => (string) $row->component_id,
                'value' => $row->value,
                'calculation_type' => $row->calculation_type?->value ?? '',
                'formula_expression' => $row->formula_expression ?? '',
                'is_mandatory' => $row->is_mandatory,
                'display_order' => $row->display_order,
            ])->values()->all();
        }

        if (empty($this->structureRows)) {
            $this->addStructureRow();
        }

        $this->showStructureModal = true;
    }

    /**
     * Add a new component row to the structure form.
     *
     * @return void
     */
    public function addStructureRow(): void
    {
        $this->structureRows[] = [
            'component_id' => '',
            'value' => '',
            'calculation_type' => '',
            'formula_expression' => '',
            'is_mandatory' => false,
            'display_order' => count($this->structureRows) + 1,
        ];
    }

    /**
     * Remove a component row from the structure form.
     *
     * @param int $index The index of the row to remove.
     * @return void
     */
    public function removeStructureRow(int $index): void
    {
        unset($this->structureRows[$index]);
        $this->structureRows = array_values($this->structureRows);
    }

    /**
     * Save the structure data (create or update).
     *
     * @return void
     */
    public function saveStructure(): void
    {
        $service = app(CompensationStructureService::class);
        $payload = [
            'structure_name' => $this->structureName,
            'description' => $this->structureDescription ?: null,
            'effective_from' => $this->structureEffectiveFrom ?: null,
            'effective_to' => $this->structureEffectiveTo ?: null,
            'is_active' => $this->structureIsActive,
            'is_default' => $this->structureIsDefault,
        ];

        $components = collect($this->structureRows)
            ->filter(fn ($row) => !empty($row['component_id']))
            ->map(fn ($row, $index) => [
                'component_id' => (int) $row['component_id'],
                'value' => $row['value'] !== '' ? $row['value'] : null,
                'calculation_type' => $row['calculation_type'] !== '' ? $row['calculation_type'] : null,
                'formula_expression' => $row['formula_expression'] !== '' ? $row['formula_expression'] : null,
                'is_mandatory' => (bool) ($row['is_mandatory'] ?? false),
                'display_order' => (int) ($row['display_order'] ?? ($index + 1)),
            ])
            ->values()
            ->all();

        try {
            if ($this->editingStructureId) {
                $service->update((int) $this->companyId, $this->editingStructureId, $payload, $components);
                session()->flash('success', 'Structure updated successfully.');
            } else {
                $service->create((int) $this->companyId, $payload, $components);
                session()->flash('success', 'Structure created successfully.');
            }
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return;
        }

        $this->showStructureModal = false;
        $this->resetStructureForm();
    }

    /**
     * Delete a compensation structure.
     *
     * @param int $structureId The ID of the structure to delete.
     * @return void
     */
    public function deleteStructure(int $structureId): void
    {
        app(CompensationStructureService::class)->delete((int) $this->companyId, $structureId);
        session()->flash('success', 'Structure deleted.');
    }

    /**
     * Reset the structure form fields and errors.
     *
     * @return void
     */
    private function resetStructureForm(): void
    {
        $this->editingStructureId = null;
        $this->structureName = '';
        $this->structureDescription = '';
        $this->structureEffectiveFrom = now()->toDateString();
        $this->structureEffectiveTo = '';
        $this->structureIsActive = true;
        $this->structureIsDefault = false;
        $this->structureRows = [];
        $this->resetErrorBag();
    }

    // --- Assignments ---

    /**
     * Handle updates to the assignment scope type.
     *
     * @return void
     */
    public function updatedAssignmentScopeType(): void
    {
        $this->assignmentScopeId = '';
        $this->inheritanceChain = [];
    }

    /**
     * Load the inheritance preview for an employee assignment.
     *
     * @return void
     */
    public function loadInheritancePreview(): void
    {
        if ($this->assignmentScopeType === 'employee' && $this->assignmentScopeId !== '') {
            $employee = Employee::where('company_id', $this->companyId)->find($this->assignmentScopeId);
            if ($employee) {
                $this->inheritanceChain = app(CompensationAssignmentService::class)
                    ->inheritanceChainForEmployee($employee);
            }
        }
    }

    /**
     * Save the structure assignment.
     *
     * @return void
     */
    public function saveAssignment(): void
    {
        try {
            app(CompensationAssignmentService::class)->assign((int) $this->companyId, [
                'scope_type' => $this->assignmentScopeType,
                'scope_id' => $this->assignmentScopeId !== '' ? (int) $this->assignmentScopeId : null,
                'structure_id' => (int) $this->assignmentStructureId,
                'effective_from' => $this->assignmentEffectiveFrom,
                'effective_to' => $this->assignmentEffectiveTo !== '' ? $this->assignmentEffectiveTo : null,
            ]);
            session()->flash('success', 'Structure assignment saved.');
            $this->assignmentStructureId = '';
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError('assignment_' . $field, $messages[0]);
            }
        }
    }

    /**
     * Delete a structure assignment.
     *
     * @param int $assignmentId The ID of the assignment to delete.
     * @return void
     */
    public function deleteAssignment(int $assignmentId): void
    {
        app(CompensationAssignmentService::class)->delete((int) $this->companyId, $assignmentId);
        session()->flash('success', 'Assignment removed.');
    }

    // --- Overrides ---

    /**
     * Open the override modal and reset its fields.
     *
     * @return void
     */
    public function openOverrideModal(): void
    {
        $this->overrideComponentId = '';
        $this->overrideType = 'REPLACE';
        $this->overrideValue = '';
        $this->overrideCalculationType = 'FIXED';
        $this->overrideEffectiveFrom = now()->toDateString();
        $this->showOverrideModal = true;
    }

    /**
     * Save the compensation override.
     *
     * @return void
     */
    public function saveOverride(): void
    {
        try {
            app(CompensationOverrideService::class)->create((int) $this->companyId, [
                'scope_type' => $this->overrideScopeType,
                'scope_id' => $this->overrideScopeId !== '' ? (int) $this->overrideScopeId : null,
                'component_id' => (int) $this->overrideComponentId,
                'override_type' => $this->overrideType,
                'value' => $this->overrideValue !== '' ? $this->overrideValue : null,
                'calculation_type' => $this->overrideCalculationType,
                'effective_from' => $this->overrideEffectiveFrom,
            ]);
            session()->flash('success', 'Override saved.');
            $this->showOverrideModal = false;
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError('override_' . $field, $messages[0]);
            }
        }
    }

    /**
     * Delete a compensation override.
     *
     * @param int $overrideId The ID of the override to delete.
     * @return void
     */
    public function deleteOverride(int $overrideId): void
    {
        app(CompensationOverrideService::class)->delete((int) $this->companyId, $overrideId);
        session()->flash('success', 'Override removed.');
    }

    /**
     * Render the Livewire component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $companyId = (int) $this->companyId;
        $components = app(CompensationComponentService::class)->listForCompany($companyId);
        $structures = app(CompensationStructureService::class)->listForCompany($companyId);
        $assignments = app(CompensationAssignmentService::class)->listForCompany($companyId);
        $locations = Location::where('company_id', $companyId)->orderBy('location_name')->get();
        $departments = Department::where('company_id', $companyId)->orderBy('department_name')->get();
        $employees = Employee::where('company_id', $companyId)->orderBy('employee_name')->get();

        $overrideScope = CompensationScopeType::from($this->overrideScopeType);
        $overrideScopeId = $this->overrideScopeId !== '' ? (int) $this->overrideScopeId : null;
        $scopeOverrides = $overrideScopeId || $overrideScope === CompensationScopeType::COMPANY
            ? app(CompensationOverrideService::class)->listForScope($companyId, $overrideScope, $overrideScopeId)
            : collect();

        $resolvedPreview = null;
        if ($this->overrideStructureId !== '') {
            $resolvedPreview = app(CompensationResolver::class)->resolveForScope(
                $companyId,
                $overrideScope,
                $overrideScopeId,
                (int) $this->overrideStructureId,
                (float) $this->overridePreviewCtc,
            );
        }

        $structurePreview = null;
        if ($this->showStructureModal && !empty($this->structureRows)) {
            $structurePreview = collect($this->structureRows)
                ->filter(fn ($row) => !empty($row['component_id']))
                ->map(function ($row) use ($components) {
                    $component = $components->firstWhere('id', (int) $row['component_id']);
                    if (!$component) {
                        return null;
                    }

                    $calcType = $row['calculation_type'] !== ''
                        ? $row['calculation_type']
                        : $component->default_calculation_type->value;
                    $value = $row['value'] !== '' ? (float) $row['value'] : (float) ($component->default_value ?? 0);
                    $monthlyCtc = ((float) $this->previewAnnualCtc) / 12;
                    $basic = collect($this->structureRows)->first(function ($r) use ($components) {
                        $c = $components->firstWhere('id', (int) ($r['component_id'] ?? 0));

                        return $c && strcasecmp($c->component_name, 'Basic') === 0;
                    });
                    $basicAmount = $basic && $basic['value'] !== '' ? (float) $basic['value'] : 0;

                    $amount = match ($calcType) {
                        'PERCENT_BASIC' => $basicAmount * ($value / 100),
                        'PERCENT_CTC' => $monthlyCtc * ($value / 100),
                        default => $value,
                    };

                    return [
                        'name' => $component->component_name,
                        'type' => $component->component_type->value,
                        'amount' => round($amount, 2),
                    ];
                })
                ->filter()
                ->values();
        }

        return view('livewire.compensation-hub', [
            'components' => $components,
            'structures' => $structures,
            'assignments' => $assignments,
            'locations' => $locations,
            'departments' => $departments,
            'employees' => $employees,
            'scopeOverrides' => $scopeOverrides,
            'resolvedPreview' => $resolvedPreview,
            'structurePreview' => $structurePreview,
        ]);
    }
}

