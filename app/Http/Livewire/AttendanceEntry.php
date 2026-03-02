<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use App\Models\MonthlyAttendance;
use App\Models\LeaveType;
use App\Models\Location;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Exports\AttendanceTemplateExport;

class AttendanceEntry extends Component
{
    use WithPagination, WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    public $month;
    public $year;
    public $attendanceData = [];
    public $isEditMode = false;
    public $selectedDepartment = '';
    public $selectedDesignation = '';
    public $departments = [];
    public $designations = [];
    public $leaveTypes = [];
    public $excel_file;
    public $selectedLocation = '';
    public $locations = [];

    protected $rules = [
        'month' => 'required|integer|min:1|max:12',
        'year' => 'required|integer|min:2000',
        'excel_file' => 'nullable|file|mimes:xlsx,xls',
    ];

    public function mount()
    {
        $companyId = session()->get('companyId');
        $this->locations = Location::where('company_id', $companyId)->get();
        $this->month = now()->month;
        $this->year = now()->year;
        $this->loadDepartmentsAndDesignations();
        $this->loadLeaveTypes();
    }

    public function loadDepartmentsAndDesignations()
    {
        $companyId = session()->get('companyIdNum');
        $locationId = $this->selectedLocation ?: session()->get('locationIdNum');
        $this->departments = Department::where('company_id', $companyId)
            ->when($locationId, function($q) use ($locationId) { return $q->where('location_id', $locationId); })
            ->get();
        $this->designations = Designation::where('company_id', $companyId)
            ->when($locationId, function($q) use ($locationId) { return $q->where('location_id', $locationId); })
            ->get();
    }

    public function loadLeaveTypes()
    {
        $companyId = session()->get('companyIdNum');
        $locationId = $this->selectedLocation ?: session()->get('locationIdNum');
        $this->leaveTypes = LeaveType::where('company_id', $companyId)
            ->when($locationId, function($q) use ($locationId) { return $q->where('location_id', $locationId); })
            ->get();
    }

    public function updatedMonth()
    {
        $this->resetPage();
        $this->attendanceData = [];
    }

    public function updatedYear()
    {
        $this->resetPage();
        $this->attendanceData = [];
    }

    public function updatedSelectedLocation()
    {
        $this->loadDepartmentsAndDesignations();
        $this->loadLeaveTypes();
        $this->attendanceData = [];
        $this->resetPage();
    }

    protected function getEmployeesQuery()
    {
        $query = Employee::whereNull('leaving_date');
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

    public function save()
    {
        $this->validateOnly('month');
        $this->validateOnly('year');
        foreach ($this->attendanceData as $employeeId => $data) {
            $leave_taken = [];
            foreach ($this->leaveTypes as $leaveType) {
                $code = strtolower($leaveType->leave_code);
                $leave_taken[$code] = $data['leave_taken'][$code] ?? 0;
            }
            MonthlyAttendance::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'month' => $this->month,
                    'year' => $this->year,
                ],
                [
                    'total_working_days' => $data['total_working_days'] ?? 0,
                    'days_present' => $data['days_present'] ?? 0,
                    'days_half_day' => $data['days_half_day'] ?? 0,
                    'days_late' => $data['days_late'] ?? 0,
                    'total_hours_worked' => $data['total_hours_worked'] ?? 0,
                    'overtime_hours' => $data['overtime_hours'] ?? 0,
                    'total_leave_days' => $data['total_leave_days'] ?? 0,
                    'leave_taken' => $leave_taken,
                    'holiday_days' => $data['holiday_days'] ?? 0,
                    'remarks' => $data['remarks'] ?? null,
                ]
            );
        }
        session()->flash('message', 'Monthly attendance saved successfully.');
        $this->isEditMode = false;
    }

    public function importExcel()
    {
        $this->validateOnly('excel_file');
        if (!$this->excel_file) {
            session()->flash('import_message', 'Please select an Excel file.');
            return;
        }
        try {
            $leaveCodes = $this->leaveTypes->pluck('leave_code')->map(function($code) { return strtolower($code); })->toArray();
            $path = $this->excel_file->getRealPath();
            $rows = Excel::toArray(null, $path)[0];
            $header = array_map('strtolower', $rows[0]);
            unset($rows[0]);
            foreach ($rows as $row) {
                $data = array_combine($header, $row);
                if (empty($data['employee_id']) || empty($data['month']) || empty($data['year'])) {
                    continue;
                }
                $leave_taken = [];
                foreach ($leaveCodes as $code) {
                    $leave_taken[$code] = isset($data[$code]) ? $data[$code] : 0;
                }
                MonthlyAttendance::updateOrCreate(
                    [
                        'employee_id' => $data['employee_id'],
                        'month' => $data['month'],
                        'year' => $data['year'],
                    ],
                    [
                        'total_working_days' => $data['total_working_days'] ?? 0,
                        'days_present' => $data['days_present'] ?? 0,
                        'days_half_day' => $data['days_half_day'] ?? 0,
                        'days_late' => $data['days_late'] ?? 0,
                        'total_hours_worked' => $data['total_hours_worked'] ?? 0,
                        'overtime_hours' => $data['overtime_hours'] ?? 0,
                        'total_leave_days' => $data['total_leave_days'] ?? 0,
                        'leave_taken' => $leave_taken,
                        'holiday_days' => $data['holiday_days'] ?? 0,
                        'remarks' => $data['remarks'] ?? null,
                    ]
                );
            }
            session()->flash('import_message', 'Monthly attendance imported successfully.');
        } catch (\Exception $e) {
            Log::error('Attendance import error: ' . $e->getMessage());
            session()->flash('import_message', 'Error importing attendance: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $companyId = session()->get('companyIdNum');
        $locationId = $this->selectedLocation ?: session()->get('locationIdNum');
        $leaveTypes = LeaveType::where('company_id', $companyId)
            ->when($locationId, function($q) use ($locationId) { return $q->where('location_id', $locationId); })
            ->get();
        $leaveCodes = $leaveTypes->pluck('leave_code')->map(function($code) { return strtoupper($code); })->toArray();
        $columns = array_merge([
            'employee_id', 'month', 'year', 'total_working_days', 'days_present', 'days_half_day', 'days_late', 'total_hours_worked', 'overtime_hours', 'total_leave_days'
        ], $leaveCodes, ['holiday_days', 'remarks']);
        $sampleRow = array_fill(0, count($columns), '');
        return \Maatwebsite\Excel\Facades\Excel::download(new AttendanceTemplateExport($columns, [$sampleRow]), 'monthly_attendance_template.xlsx');
    }

    public function render()
    {
        $employees = $this->getEmployeesQuery()->paginate(10);
        foreach ($employees as $employee) {
            $attendance = MonthlyAttendance::where('employee_id', $employee->id)
                ->where('month', $this->month)
                ->where('year', $this->year)
                ->first();
            $leave_taken = [];
            foreach ($this->leaveTypes as $leaveType) {
                $code = strtolower($leaveType->leave_code);
                $leave_taken[$code] = $attendance->leave_taken[$code] ?? 0;
            }
            $this->attendanceData[$employee->id] = [
                'total_working_days' => $attendance->total_working_days ?? 0,
                'days_present' => $attendance->days_present ?? 0,
                'days_half_day' => $attendance->days_half_day ?? 0,
                'days_late' => $attendance->days_late ?? 0,
                'total_hours_worked' => $attendance->total_hours_worked ?? 0,
                'overtime_hours' => $attendance->overtime_hours ?? 0,
                'total_leave_days' => $attendance->total_leave_days ?? 0,
                'leave_taken' => $leave_taken,
                'holiday_days' => $attendance->holiday_days ?? 0,
                'remarks' => $attendance->remarks ?? '',
            ];
        }
        return view('livewire.attendance-entry', [
            'employees' => $employees,
            'leaveTypes' => $this->leaveTypes,
            'locations' => $this->locations,
            'selectedLocation' => $this->selectedLocation,
        ]);
    }
} 