<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\CompensationStructure;
use App\Models\Department;
use App\Models\Designation;

class CompensationStructureManager extends Component
{
    public $showModal = false;
    public $editMode = false;
    public $selectedId = null;
    public $designation_id = '';
    public $department_id = '';

    public $company_id = '';
    public $name = '';
    public $compensations = [];
    public $availableTypes = ['BASIC', 'HRA', 'DA', 'TA', 'MA', 'Other Allowances'];
    public $departments = [];
    public $designations = [];

    protected $rules = [
        'designation_id' => 'required|exists:designations,id',
        'department_id' => 'required|exists:departments,id',
        'compensations.*.type' => 'required|string',
        'compensations.*.percentage' => 'required|numeric|min:0|max:100',
    ];

    public function mount()
    {
        $this->company_id = session()->get("companyIdNum");
        $this->departments = Department::where('company_id', $this->company_id)->get();
        $this->designations = Designation::where('company_id', $this->company_id)->get();
        
        $this->addCompensationRow();
    }

    public function addCompensationRow()
    {
        $this->compensations[] = [
            'type' => '',
            'percentage' => '',
        ];
    }

    public function removeCompensationRow($index)
    {
        unset($this->compensations[$index]);
        $this->compensations = array_values($this->compensations);
    }

    public function editCompensation($id)
    {
        $compensation = CompensationStructure::findOrFail($id);
        $this->selectedId = $id;
        $this->designation_id = $compensation->designation_id;
        $this->department_id = $compensation->department_id;
        $this->compensations = json_decode($compensation->structure, true);
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

        // Check for duplicate compensation types
        $types = array_column($this->compensations, 'type');
        if (count($types) !== count(array_unique($types))) {
            $this->addError('compensations', 'Duplicate compensation types are not allowed.');
            return;
        }

        $data = [
            'company_id' => $this->company_id,
            'designation_id' => $this->designation_id,
            'department_id' => $this->department_id,
            'name' =>$this->department_id,
            'structure' => json_encode($this->compensations),
        ];

        if ($this->editMode) {
            CompensationStructure::findOrFail($this->selectedId)->update($data);
            session()->flash('success', 'Compensation structure updated successfully.');
        } else {
            CompensationStructure::create($data);
            session()->flash('success', 'Compensation structure created successfully.');
        }

        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset([
            'showModal',
            'editMode',
            'selectedId',
            'designation_id',
            'department_id',
            'compensations',
        ]);
        $this->addCompensationRow();
    }

    public function render()
    {
        $structures = CompensationStructure::with(relations: ['designation', 'department'])->get();
        return view('livewire.compensation-structure', [
            'structures' => $structures
        ]);
    }
} 