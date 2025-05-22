<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Designation;
use Carbon\Carbon;

class AttendanceEntry extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $from_date;
    public $to_date;
    public $attendanceData = [];
    public $isEditMode = false;
    public $selectedDepartment = '';
    public $selectedDesignation = '';
    public $departments = [];
    public $designations = [];

    protected $rules = [
        'from_date' => 'required|date',
        'to_date' => 'required|date|after_or_equal:from_date',
        'attendanceData.*.casual_leave' => 'nullable|numeric|min:0',
        'attendanceData.*.earned_leave' => 'nullable|numeric|min:0',
        'attendanceData.*.maternity_leave' => 'nullable|numeric|min:0',
        'attendanceData.*.earnings.*.name' => 'required_with:attendanceData.*.earnings.*.amount|string',
        'attendanceData.*.earnings.*.amount' => 'required_with:attendanceData.*.earnings.*.name|numeric|min:0',
        'attendanceData.*.deductions.*.name' => 'required_with:attendanceData.*.deductions.*.amount|string',
        'attendanceData.*.deductions.*.amount' => 'required_with:attendanceData.*.deductions.*.amount|numeric|min:0',
    ];

    public function mount()
    {
        $this->from_date = Carbon::now()->format('Y-m-d');
        $this->to_date = Carbon::now()->format('Y-m-d');
        $this->loadDepartmentsAndDesignations();
    }

    public function loadDepartmentsAndDesignations()
    {
        $companyId = session()->get("companyIdNum");
        $this->departments = Department::where('company_id', $companyId)->get();
        $this->designations = Designation::where('company_id', $companyId)->get();
    }

    public function updatedFromDate()
    {
        $this->validateOnly('from_date');
        if ($this->to_date && Carbon::parse($this->from_date)->gt(Carbon::parse($this->to_date))) {
            $this->to_date = $this->from_date;
        }
        $this->loadExistingAttendance();
    }

    public function updatedToDate()
    {
        $this->validateOnly('to_date');
        $this->loadExistingAttendance();
    }

    public function loadExistingAttendance()
    {
        if ($this->from_date && $this->to_date) {
            $employees = $this->getEmployeesQuery()->get();
            
            foreach ($employees as $employee) {
                $attendance = Attendance::where('employee_id', $employee->id)
                    ->where(function($query) {
                        $query->whereBetween('from_date', [$this->from_date, $this->to_date])
                              ->orWhereBetween('to_date', [$this->from_date, $this->to_date]);
                    })
                    ->first();

                if ($attendance) {
                    $this->attendanceData[$employee->id] = [
                        'casual_leave' => $attendance->casual_leave,
                        'earned_leave' => $attendance->earned_leave,
                        'maternity_leave' => $attendance->maternity_leave,
                        'earnings' => $attendance->earnings ?? [],
                        'deductions' => $attendance->deductions ?? []
                    ];
                }
            }
        }
    }

    protected function getEmployeesQuery()
    {
        $query = Employee::whereNull('employee_leaving_date')
            ->with(['department', 'designation']);

        if ($this->selectedDepartment) {
            $query->where('department_id', $this->selectedDepartment);
        }

        if ($this->selectedDesignation) {
            $query->where('designation_id', $this->selectedDesignation);
        }

        return $query;
    }

    public function toggleEditMode()
    {
        $this->isEditMode = !$this->isEditMode;
    }

    public function addEarning($employeeId)
    {
        $this->attendanceData[$employeeId]['earnings'][] = ['name' => '', 'amount' => ''];
    }

    public function removeEarning($employeeId, $index)
    {
        unset($this->attendanceData[$employeeId]['earnings'][$index]);
        $this->attendanceData[$employeeId]['earnings'] = array_values($this->attendanceData[$employeeId]['earnings']);
    }

    public function addDeduction($employeeId)
    {
        $this->attendanceData[$employeeId]['deductions'][] = ['name' => '', 'amount' => ''];
    }

    public function removeDeduction($employeeId, $index)
    {
        unset($this->attendanceData[$employeeId]['deductions'][$index]);
        $this->attendanceData[$employeeId]['deductions'] = array_values($this->attendanceData[$employeeId]['deductions']);
    }

    public function getDaysProperty()
    {
        if ($this->from_date && $this->to_date) {
            return Carbon::parse($this->from_date)->diffInDays(Carbon::parse($this->to_date)) + 1;
        }
        return 0;
    }

    public function save()
    {
        $this->validate();

        foreach ($this->attendanceData as $employeeId => $data) {
            Attendance::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'from_date' => $this->from_date,
                    'to_date' => $this->to_date
                ],
                [
                    'days' => $this->days,
                    'casual_leave' => $data['casual_leave'] ?? 0,
                    'earned_leave' => $data['earned_leave'] ?? 0,
                    'maternity_leave' => $data['maternity_leave'] ?? 0,
                    'earnings' => $data['earnings'],
                    'deductions' => $data['deductions']
                ]
            );
        }

        session()->flash('message', 'Attendance data saved successfully.');
    }

    public function render()
    {
        $employees = $this->getEmployeesQuery()->paginate(10);

        // Initialize attendance data for new employees
        foreach ($employees as $employee) {
            if (!isset($this->attendanceData[$employee->id])) {
                $this->attendanceData[$employee->id] = [
                    'casual_leave' => 0,
                    'earned_leave' => 0,
                    'maternity_leave' => 0,
                    'earnings' => [],
                    'deductions' => []
                ];
            }
        }

        return view('livewire.attendance-entry', [
            'employees' => $employees
        ]);
    }
} 