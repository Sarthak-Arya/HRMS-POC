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

    public function mount()
    {
        $this->companyId = session()->get("companyIdNum");
    }

    public function render()
    {
        $query = Employee::where('company_id', $this->companyId)
            ->with(['designation', 'department']);

        if ($this->selectedDesignation) {
            $query->where('designation_id', $this->selectedDesignation);
        }

        if ($this->selectedDepartment) {
            $query->where('department_id', $this->selectedDepartment);
        }

        if ($this->selectedStatus !== '') {
            if ($this->selectedStatus === 'active') {
                $query->whereNull('employee_leaving_date');
            } else {
                $query->whereNotNull('employee_leaving_date');
            }
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('employee_first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('employee_last_name', 'like', '%' . $this->search . '%');
            });
        }

        $employees = $query->paginate(10);
        $designations = Designation::where('company_id', $this->companyId)->get();
        $departments = Department::where('company_id', $this->companyId)->get();

        return view('livewire.employee-list', [
            'employees' => $employees,
            'designations' => $designations,
            'departments' => $departments
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
} 