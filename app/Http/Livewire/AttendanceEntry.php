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
use Illuminate\Support\Facades\Schema;
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
    public int $deductionCount = 1;

    protected $rules = [
        'month' => 'required|integer|min:1|max:12',
        'year' => 'required|integer|min:2000',
        'excel_file' => 'nullable|file|mimes:xlsx,xls',
    ];

    public function mount()
    {
        $companyId = session()->get('companyId');
        $this->locations = $companyId ? Location::where('company_id', $companyId)->get() : collect();
        $this->month = now()->month;
        $this->year = now()->year;
        $this->loadDepartmentsAndDesignations();
        $this->loadLeaveTypes();
    }

    public function loadDepartmentsAndDesignations()
    {
        $companyId = session()->get('companyId');
        $this->departments = $companyId ? Department::where('company_id', $companyId)->get() : collect();
        $this->designations = $companyId ? Designation::where('company_id', $companyId)->get() : collect();
    }

    public function loadLeaveTypes()
    {
        if (!Schema::hasTable('leave_types')) {
            $this->leaveTypes = collect();
            return;
        }

        $companyId = session()->get('companyId');
        $locationId = $this->selectedLocation ?: null;
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
        $companyId = session()->get('companyId');
        $query = Employee::query()
            ->where('company_id', $companyId)
            ->whereNull('dol')
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

    public function addDeductionColumn(): void
    {
        $this->deductionCount = min($this->deductionCount + 1, 20);
    }

    public function removeDeductionColumn(): void
    {
        $this->deductionCount = max($this->deductionCount - 1, 1);
    }

    public function save()
    {
        $this->validateOnly('month');
        $this->validateOnly('year');

        $companyId = session()->get('companyId');
        if (!$companyId || !Schema::hasTable('attendance')) {
            session()->flash('message', 'Attendance table is not configured.');
            $this->isEditMode = false;
            return;
        }

        foreach ($this->attendanceData as $employeeId => $data) {
            $cl = (float) ($data['cl'] ?? 0);
            $el = (float) ($data['el'] ?? 0);
            $sl = (float) ($data['sl'] ?? 0);
            $esiLeave = (float) ($data['esi_leave'] ?? 0);
            $holiday = (float) ($data['holiday'] ?? 0);
            $totalDays = (float) ($data['tot_dys'] ?? 0);
            $workedDays = max(0, $totalDays - ($cl + $el + $sl + $esiLeave + $holiday));

            $deductions = array_values(array_map(
                fn ($v) => (float) $v,
                array_slice(($data['deductions'] ?? []), 0, $this->deductionCount)
            ));

            MonthlyAttendance::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'company_id' => $companyId,
                    'month' => $this->month,
                    'year' => $this->year,
                ],
                [
                    'casual_leave' => $cl,
                    'earned_leave' => $el,
                    'sick_leave' => $sl,
                    'esi_la' => $esiLeave,
                    'holiday' => $holiday,
                    'total_days' => $totalDays,
                    'worked_days' => $workedDays,
                    'deductions' => $deductions,
                    // Keep legacy columns populated for backward compatibility.
                    'ded_1' => $deductions[0] ?? 0,
                    'ded_2' => $deductions[1] ?? 0,
                    'ded_3' => $deductions[2] ?? 0,
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
            $companyId = session()->get('companyId');
            if (!$companyId || !Schema::hasTable('attendance')) {
                session()->flash('import_message', 'Attendance table is not configured.');
                return;
            }

            $path = $this->excel_file->getRealPath();
            $rows = Excel::toArray(null, $path)[0];
            $header = array_map('strtolower', $rows[0]);
            unset($rows[0]);
            foreach ($rows as $row) {
                $data = array_combine($header, $row);
                if (empty($data['employee_id']) || empty($data['month']) || empty($data['year'])) {
                    continue;
                }

                $cl = (float) ($data['cl'] ?? 0);
                $el = (float) ($data['el'] ?? 0);
                $sl = (float) ($data['sl'] ?? 0);
                $esiLeave = (float) ($data['esi_leave'] ?? 0);
                $holiday = (float) ($data['holiday'] ?? 0);
                $totalDays = (float) ($data['tot_dys'] ?? 0);
                $workedDays = max(0, $totalDays - ($cl + $el + $sl + $esiLeave + $holiday));

                $deductions = [];
                for ($i = 1; $i <= 50; $i++) {
                    $key = 'ded_' . $i;
                    if (!array_key_exists($key, $data)) {
                        break;
                    }
                    $deductions[] = (float) ($data[$key] ?? 0);
                }

                MonthlyAttendance::updateOrCreate(
                    [
                        'employee_id' => $data['employee_id'],
                        'company_id' => $companyId,
                        'month' => $data['month'],
                        'year' => $data['year'],
                    ],
                    [
                        'casual_leave' => $cl,
                        'earned_leave' => $el,
                        'sick_leave' => $sl,
                        'esi_la' => $esiLeave,
                        'holiday' => $holiday,
                        'total_days' => $totalDays,
                        'worked_days' => $workedDays,
                        'deductions' => $deductions,
                        'ded_1' => $deductions[0] ?? 0,
                        'ded_2' => $deductions[1] ?? 0,
                        'ded_3' => $deductions[2] ?? 0,
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
        $columns = [
            'employee_id',
            'month',
            'year',
            'cl',
            'el',
            'sl',
            'esi_leave',
            'holiday',
            'tot_dys',
        ];
        for ($i = 1; $i <= $this->deductionCount; $i++) {
            $columns[] = 'ded_' . $i;
        }
        $sampleRow = array_fill(0, count($columns), '');
        return \Maatwebsite\Excel\Facades\Excel::download(new AttendanceTemplateExport($columns, [$sampleRow]), 'monthly_attendance_template.xlsx');
    }

    public function render()
    {
        $employees = $this->getEmployeesQuery()->paginate(10);
        $companyId = session()->get('companyId');
        foreach ($employees as $employee) {
            $attendance = Schema::hasTable('attendance')
                ? MonthlyAttendance::where('employee_id', $employee->id)
                    ->where('company_id', $companyId)
                    ->where('month', $this->month)
                    ->where('year', $this->year)
                    ->first()
                : null;

            if (!$this->isEditMode || !isset($this->attendanceData[$employee->id])) {
                $existingDeductions = [];
                if ($attendance) {
                    $existingDeductions = is_array($attendance->deductions ?? null) ? $attendance->deductions : [];
                    if (empty($existingDeductions)) {
                        // Backwards-compat: if legacy columns exist, use them as seed values.
                        $existingDeductions = array_values(array_filter([
                            $attendance->ded_1 ?? null,
                            $attendance->ded_2 ?? null,
                            $attendance->ded_3 ?? null,
                        ], fn ($v) => $v !== null));
                    }
                }
                $this->deductionCount = max($this->deductionCount, max(1, count($existingDeductions)));

                $this->attendanceData[$employee->id] = [
                    'cl' => $attendance->casual_leave ?? 0,
                    'el' => $attendance->earned_leave ?? 0,
                    'sl' => $attendance->sick_leave ?? 0,
                    'esi_leave' => $attendance->esi_la ?? 0,
                    'holiday' => $attendance->holiday ?? 0,
                    'tot_dys' => $attendance->total_days ?? 0,
                    'deductions' => $existingDeductions,
                ];
            }

            // Dynamic leave types are not used in the current UI.
        }
        return view('livewire.attendance-entry', [
            'employees' => $employees,
            'leaveTypes' => $this->leaveTypes,
            'locations' => $this->locations,
            'selectedLocation' => $this->selectedLocation,
        ]);
    }
} 
