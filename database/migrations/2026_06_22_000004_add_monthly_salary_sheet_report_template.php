<?php

use App\Models\CompanyReportTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        CompanyReportTemplate::query()->updateOrCreate(
            ['slug' => 'monthly-salary-sheet'],
            [
                'company_id' => null,
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
                'default_format' => 'pdf',
                'is_active' => true,
            ],
        );
    }

    public function down(): void
    {
        CompanyReportTemplate::query()->where('slug', 'monthly-salary-sheet')->delete();
    }
};
