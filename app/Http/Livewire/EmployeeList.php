<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Employee;
use App\Models\Designation;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;

class EmployeeList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $companyId;
    public $selectedDesignation = '';
    public $selectedDepartment = '';
    public $selectedStatus = '';
    public $search = '';
    public $selectedLocation = '';

    public function mount(?string $company_id = null)
    {
        $this->companyId = $company_id ?? session()->get("companyId");
        if ($this->companyId) {
            session()->put('companyId', $this->companyId);
        }
    }

    public function render()
    {
        $query = Employee::where('company_id', $this->companyId)
            ->with(['designation', 'department', 'location']);

        if ($this->selectedDesignation) {
            $query->where('designation_id', $this->selectedDesignation);
        }

        if ($this->selectedDepartment) {
            $query->where('department_id', $this->selectedDepartment);
        }

        if ($this->selectedLocation) {
            $query->where('location_id', $this->selectedLocation);
        }

        if ($this->selectedStatus !== '') {
            if ($this->selectedStatus === 'active') {
                $query->whereNull('dol');
            } else {
                $query->whereNotNull('dol');
            }
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('employee_name', 'like', '%' . $this->search . '%')
                  ->orWhere('employee_code', 'like', '%' . $this->search . '%');
            });
        }

        $employees = $query->paginate(30);
        $designations = Designation::where('company_id', $this->companyId)->get();
        $departments = Department::where('company_id', $this->companyId)->get();
        $locations = \App\Models\Location::where('company_id', $this->companyId)->get();

        return view('livewire.employee-list', [
            'employees' => $employees,
            'designations' => $designations,
            'departments' => $departments,
            'locations' => $locations,
            'companyId' => $this->companyId,
        ]);
    }

    public function updatedSelectedDesignation()
    {
        $this->resetPage();
    }

    public function updatedSelectedDepartment()
    {
        $this->resetPage();
    }

    public function updatedSelectedStatus()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedLocation()
    {
        $this->resetPage();
    }
} 
