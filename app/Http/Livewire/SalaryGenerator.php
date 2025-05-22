<?php

namespace App\Http\Livewire;

use App\Jobs\ProcessEmployeeSalary;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Models\Salary;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class SalaryGenerator extends Component
{
    public $selectedDepartment = '';
    public $selectedDesignation = '';
    public $fromDate = '';
    public $toDate = '';
    public $dateRanges = [];
    public $departments = [];
    public $designations = [];
    public $batchId = null;
    public $progress = 0;
    public $status = '';

    protected $rules = [
        'selectedDepartment' => 'nullable|exists:departments,id',
        'selectedDesignation' => 'nullable|exists:designations,id',
        'fromDate' => 'required|date',
        'toDate' => 'required|date|after_or_equal:fromDate'
    ];

    public function mount()
    {
        $this->loadDepartmentsAndDesignations();
        $this->loadDateRanges();
    }

    public function loadDepartmentsAndDesignations()
    {
        $companyId = session()->get("companyIdNum");
        $this->departments = Department::where('company_id', $companyId)->get();
        $this->designations = Designation::where('company_id', $companyId)->get();
    }

    public function loadDateRanges()
    {
        $this->dateRanges = DB::table('attendances')
            ->select(DB::raw('DISTINCT from_date, to_date'))
            ->orderBy('from_date', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->from_date . '|' . $item->to_date,
                    'label' => $item->from_date . ' to ' . $item->to_date
                ];
            });
    }

    public function updatedFromDate()
    {
        $this->validateOnly('fromDate');
        if ($this->toDate && $this->toDate < $this->fromDate) {
            $this->toDate = $this->fromDate;
        }
    }

    public function updatedToDate()
    {
        $this->validateOnly('toDate');
    }

    public function generateSalaries()
    {
        $this->validate();

        // Check if salaries already exist
        if (Salary::where('from_date', $this->fromDate)
            ->where('to_date', $this->toDate)
            ->exists()) {
            session()->flash('error', 'Salaries already exist for the selected date range.');
            return;
        }

        // Get employees based on filters
        $query = Employee::whereNull('employee_leaving_date')
            ->whereNotNull('compensation_id');

        if ($this->selectedDepartment) {
            $query->where('department_id', $this->selectedDepartment);
        }

        if ($this->selectedDesignation) {
            $query->where('designation_id', $this->selectedDesignation);
        }

        $employees = $query->get();

        if ($employees->isEmpty()) {
            session()->flash('error', 'No employees found matching the selected criteria.');
            return;
        }

        // Create jobs for each employee
        $jobs = $employees->map(function ($employee) {
            return new ProcessEmployeeSalary($employee, $this->fromDate, $this->toDate);
        });

        // Create batch record
        $payrollBatch = PayrollBatch::create([
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'department_id' => $this->selectedDepartment,
            'designation_id' => $this->selectedDesignation,
            'total_jobs' => $jobs->count(),
            'status' => 'processing'
        ]);

        // Dispatch batch
        $busBatch = Bus::batch($jobs)
            ->allowFailures()
            ->then(function () use ($payrollBatch) {
                $payrollBatch->update(['status' => 'completed']);
            })
            ->catch(function ($batch, $e) use ($payrollBatch) {
                $payrollBatch->update(['status' => 'failed']);
                Log::error('Batch failed: ' . $e->getMessage());
            })
            ->finally(function () use ($payrollBatch) {
                $payrollBatch->update(['processed_jobs' => $payrollBatch->total_jobs]);
            })
            ->dispatch();

        $this->batchId = $busBatch->id;
        $payrollBatch->update(['batch_id' => $this->batchId]);
    }

    public function getProgressProperty()
    {
        if (!$this->batchId) {
            return 0;
        }

        $batch = PayrollBatch::where('batch_id', $this->batchId)->first();
        if (!$batch) {
            return 0;
        }

        $this->status = $batch->status;
        return $batch->total_jobs > 0 
            ? round(($batch->processed_jobs / $batch->total_jobs) * 100) 
            : 0;
    }

    public function render()
    {
        return view('livewire.salary-generator');
    }
} 