<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\CompensationStructure;
use App\Models\CompensationComponent;
use App\Models\CompensationStructureComponent;
use App\Models\Department;
use App\Models\Designation;

class CompensationStructureManager extends Component
{
    public $showModal = false;
    public $editMode = false;
    public $selectedId = null;
    public $applies_to_type = 'department'; // default, can be changed as needed
    public $applies_to_id = '';
    public $company_id = '';
    public $name = '';
    public $compensations = [];
    public $availableComponents = [];
    public $departments = [];
    public $designations = [];

    protected $rules = [
        'name' => 'required|string',
        'applies_to_type' => 'required|in:company,department,location,employee',
        'applies_to_id' => 'nullable|integer',
        'compensations.*.component_id' => 'required|exists:compensation_components,id',
        'compensations.*.amount_type' => 'required|in:fixed,percentage',
        'compensations.*.value' => 'required|numeric|min:0',
    ];

    public function mount()
    {
        $this->company_id = session()->get("companyIdNum");
        $this->departments = Department::where('company_id', $this->company_id)->get();
        $this->designations = Designation::where('company_id', $this->company_id)->get();
        $this->availableComponents = CompensationComponent::all();
        $this->addCompensationRow();
    }

    public function addCompensationRow()
    {
        $this->compensations[] = [
            'component_id' => '',
            'amount_type' => 'fixed',
            'value' => '',
        ];
    }

    public function removeCompensationRow($index)
    {
        unset($this->compensations[$index]);
        $this->compensations = array_values($this->compensations);
    }

    public function editCompensation($id)
    {
        $structure = CompensationStructure::with('components')->findOrFail($id);
        $this->selectedId = $id;
        $this->name = $structure->name;
        $this->applies_to_type = $structure->applies_to_type;
        $this->applies_to_id = $structure->applies_to_id;
        $this->compensations = [];
        foreach ($structure->components as $component) {
            $this->compensations[] = [
                'component_id' => $component->id,
                'amount_type' => $component->pivot->amount_type,
                'value' => $component->pivot->value,
            ];
        }
        $this->editMode = true;
        $this->showModal = true;
    }

    public function deleteCompensation($id)
    {
        CompensationStructure::findOrFail($id)->delete();
        session()->flash('success', 'Compensation structure deleted successfully.');
    }

    public function save()
    {
        $this->validate();

        // Check for duplicate component_ids
        $componentIds = array_column($this->compensations, 'component_id');
        if (count($componentIds) !== count(array_unique($componentIds))) {
            $this->addError('compensations', 'Duplicate compensation components are not allowed.');
            return;
        }

        $data = [
            'company_id' => $this->company_id,
            'name' => $this->name,
            'applies_to_type' => $this->applies_to_type,
            'applies_to_id' => $this->applies_to_id,
        ];

        if ($this->editMode) {
            $structure = CompensationStructure::findOrFail($this->selectedId);
            $structure->update($data);
        } else {
            $structure = CompensationStructure::create($data);
        }

        // Sync components in the pivot table
        $syncData = [];
        foreach ($this->compensations as $row) {
            $syncData[$row['component_id']] = [
                'amount_type' => $row['amount_type'],
                'value' => $row['value'],
            ];
        }
        $structure->components()->sync($syncData);

        session()->flash('success', $this->editMode ? 'Compensation structure updated successfully.' : 'Compensation structure created successfully.');
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset([
            'showModal',
            'editMode',
            'selectedId',
            'name',
            'applies_to_type',
            'applies_to_id',
            'compensations',
        ]);
        $this->addCompensationRow();
    }

    public function render()
    {
        $structures = CompensationStructure::with(['components', 'company'])->where('company_id', $this->company_id)->get();
        return view('livewire.compensation-structure', [
            'structures' => $structures,
            'availableComponents' => $this->availableComponents,
            'departments' => $this->departments,
            'designations' => $this->designations,
        ]);
    }
} 