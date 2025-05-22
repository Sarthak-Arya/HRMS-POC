<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Carbon\Carbon;

class EmployeeImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Find department by name
        $department = Department::where('department_name', $row['department'])
            ->where('company_id', request()->session()->get('companyIdNum'))
            ->first();

        // Find designation by name
        $designation = Designation::where('designation_name', $row['designation'])
            ->where('company_id', request()->session()->get('companyIdNum'))
            ->first();

        return new Employee([
            'company_id' => request()->session()->get('companyIdNum'),
            'employee_first_name' => $row['first_name'],
            'employee_middle_name' => $row['middle_name'],
            'employee_last_name' => $row['last_name'],
            'employee_father_name' => $row['father_name'],
            'employee_gender' => $row['gender'],
            'employee_dob' => $row['date_of_birth'],
            'employee_age' => $row['age'],
            'employee_company_code' => $row['company_code'],
            'employee_joining_date' => $row['joining_date'],
            'employee_leaving_date' => $row['leaving_date'],
            'employee_esi_no' => $row['esi_number'],
            'employee_pf_no' => $row['pf_number'],
            'department_id' => $department ? $department->id : null,
            'designation_id' => $designation ? $designation->id : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required',
            'last_name' => 'required',
            'father_name' => 'required',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date',
            'department' => 'required',
            'designation' => 'required',
        ];
    }

    public function headingRow(): int
    {
        return 1;
    }
}
