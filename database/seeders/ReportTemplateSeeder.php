<?php

namespace Database\Seeders;

use App\Models\CompanyReportTemplate;
use Illuminate\Database\Seeder;

class ReportTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug' => 'employee-master',
                'name' => 'Employee Master',
                'data_source' => 'employees',
                'config' => [
                    'columns' => [
                        'employee_code',
                        'employee_name',
                        'gender',
                        'father_name',
                        'dob',
                        'doj',
                        'dol',
                        'pf_no',
                        'esi_no',
                        'department.department_name',
                        'designation.designation_name',
                        'location.name',
                    ],
                    'filters' => [],
                    'sort' => [
                        ['field' => 'employee_code', 'dir' => 'asc'],
                    ],
                    'parameters' => ['department_id'],
                ],
            ],
            [
                'slug' => 'monthly-attendance-register',
                'name' => 'Monthly Attendance Register',
                'data_source' => 'attendance_monthly',
                'config' => [
                    'columns' => [
                        'employee.employee_code',
                        'employee.employee_name',
                        'employee.department.department_name',
                        'month',
                        'year',
                        'working_days',
                        'present_days',
                        'half_days',
                        'paid_leave_days',
                        'lop_days',
                        'holiday_days',
                        'weekly_off_days',
                        'overtime_hours',
                        'worked_days',
                        'total_days',
                    ],
                    'filters' => [
                        ['field' => 'month', 'op' => '=', 'param' => 'month'],
                        ['field' => 'year', 'op' => '=', 'param' => 'year'],
                        ['field' => 'employee.department_id', 'op' => '=', 'param' => 'department_id'],
                    ],
                    'sort' => [
                        ['field' => 'employee.employee_code', 'dir' => 'asc'],
                    ],
                    'parameters' => ['month', 'year', 'department_id'],
                ],
            ],
            [
                'slug' => 'monthly-salary-sheet',
                'name' => 'Monthly Salary Sheet',
                'data_source' => 'salary_sheet',
                'config' => [
                    'columns' => [
                        'employee.employee_code',
                        'employee.employee_name',
                        'employee.father_name',
                        'employee.department.department_name',
                        'work_days',
                        'holiday_days',
                        'total_days',
                        'actual_gross',
                        'payable_gross',
                        'total_deductions',
                        'net_pay',
                    ],
                    'filters' => [
                        ['field' => 'payroll_run_id', 'op' => '=', 'param' => 'payroll_run_id'],
                    ],
                    'sort' => [
                        ['field' => 'employee.employee_code', 'dir' => 'asc'],
                    ],
                    'parameters' => ['payroll_run_id', 'group_by', 'format'],
                ],
            ],
            [
                'slug' => 'payroll-register',
                'name' => 'Payroll Register',
                'data_source' => 'payroll_register',
                'config' => [
                    'columns' => [
                        'employee.employee_code',
                        'employee.employee_name',
                        'employee.department.department_name',
                        'payrollRun.month',
                        'payrollRun.year',
                        'gross_earnings',
                        'gross_deductions',
                        'employer_contributions',
                        'net_pay',
                        'status',
                    ],
                    'filters' => [
                        ['field' => 'payroll_run_id', 'op' => '=', 'param' => 'payroll_run_id'],
                        ['field' => 'employee.department_id', 'op' => '=', 'param' => 'department_id'],
                    ],
                    'sort' => [
                        ['field' => 'employee.employee_code', 'dir' => 'asc'],
                    ],
                    'parameters' => ['payroll_run_id', 'department_id'],
                ],
            ],
        ];

        foreach ($templates as $template) {
            CompanyReportTemplate::query()->updateOrCreate(
                ['slug' => $template['slug']],
                [
                    'company_id' => null,
                    'name' => $template['name'],
                    'data_source' => $template['data_source'],
                    'config' => $template['config'],
                    'default_format' => $template['data_source'] === 'salary_sheet' ? 'pdf' : 'xlsx',
                    'is_active' => true,
                ],
            );
        }
    }
}

