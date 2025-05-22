<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromQuery, WithHeadings, WithMapping
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'First Name',
            'Middle Name',
            'Last Name',
            'Father Name',
            'Gender',
            'Date of Birth',
            'Age',
            'Company Code',
            'Joining Date',
            'Leaving Date',
            'ESI Number',
            'PF Number',
            'Department',
            'Designation'
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->employee_first_name,
            $employee->employee_middle_name,
            $employee->employee_last_name,
            $employee->employee_father_name,
            $employee->employee_gender,
            $employee->employee_dob,
            $employee->employee_age,
            $employee->employee_company_code,
            $employee->employee_joining_date,
            $employee->employee_leaving_date,
            $employee->employee_esi_no,
            $employee->employee_pf_no,
            $employee->department ? $employee->department->department_name : 'N/A',
            $employee->designation ? $employee->designation->designation_name : 'N/A'
        ];
    }
} 