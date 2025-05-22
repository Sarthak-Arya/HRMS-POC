<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;

class EmployeesImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $companyId;
    protected $departmentId;

    public function __construct($companyId, $departmentId)
    {
        $this->companyId = $companyId;
        $this->departmentId = $departmentId;
    }

    public function model(array $row)
    {
        $departmentId = $this->departmentId;

        // If importing for all departments, get department ID from the Excel
        if ($this->departmentId === 'all') {
            $department = Department::where('company_id', $this->companyId)
                ->where('department_name', $row['department'])
                ->first();

            if (!$department) {
                Log::error("Department not found: " . $row['department']);
                return null;
            }

            $departmentId = $department->id;
        }

        return new Employee([
            'company_id' => $this->companyId,
            'employee_first_name' => $row['first_name'],
            'employee_middle_name' => $row['middle_name'] ?? null,
            'employee_last_name' => $row['last_name'],
            'employee_father_name' => $row['father_name'],
            'employee_gender' => $row['gender'],
            'employee_dob' => $row['date_of_birth'],
            'employee_company_code' => $row['company_code'],
            'employee_joining_date' => $row['joining_date'],
            'employee_leaving_date' => $row['leaving_date'] ?? null,
            'employee_esi_no' => $row['esi_number'] ?? null,
            'employee_pf_no' => $row['pf_number'] ?? null,
            'department_id' => $departmentId,
            'designation_id' => $row['designation'],
            'employee_age' => $this->calculateAge($row['date_of_birth'])
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'father_name' => 'required|string',
            'gender' => 'required|in:Male,Female,Other',
            'date_of_birth' => 'required|date',
            'company_code' => 'required|string',
            'joining_date' => 'required|date',
            'designation' => 'required|exists:designations,id',
        ];

        // If importing for all departments, require department column
        if ($this->departmentId === 'all') {
            $rules['department'] = 'required|numeric';
        }

        return $rules;
    }

    private function calculateAge($dob)
    {
        return \Carbon\Carbon::parse($dob)->age;
    }
} 