<?php

namespace App\Services\Reports\DataSources;

use App\Enums\Reports\ReportColumnFormat;
use App\Enums\Reports\ReportDataSourceKey;
use App\Models\EmployeePayroll;
use Illuminate\Database\Eloquent\Builder;

class SalarySheetDataSource implements ReportDataSource
{
    public function key(): ReportDataSourceKey
    {
        return ReportDataSourceKey::SalarySheet;
    }

    public function label(): string
    {
        return ReportDataSourceKey::SalarySheet->label();
    }

    public function columns(): array
    {
        return [
            'employee.employee_code' => 'Employee Code',
            'employee.employee_name' => 'Employee Name',
            'employee.father_name' => "Father's Name",
            'employee.department.department_name' => 'Department',
            'employee.location.name' => 'Location',
            'work_days' => 'Work Days',
            'holiday_days' => 'Holiday Days',
            'total_days' => 'Total Days',
            'actual_gross' => 'Actual Gross',
            'payable_gross' => 'Payable Gross',
            'total_deductions' => 'Total Deductions',
            'net_pay' => 'Net Pay',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'employee.employee_code' => ReportColumnFormat::Text->value,
            'employee.employee_name' => ReportColumnFormat::Text->value,
            'employee.father_name' => ReportColumnFormat::Text->value,
            'employee.department.department_name' => ReportColumnFormat::Text->value,
            'employee.location.name' => ReportColumnFormat::Text->value,
            'work_days' => ReportColumnFormat::Number->value,
            'holiday_days' => ReportColumnFormat::Number->value,
            'total_days' => ReportColumnFormat::Number->value,
            'actual_gross' => ReportColumnFormat::Currency->value,
            'payable_gross' => ReportColumnFormat::Currency->value,
            'total_deductions' => ReportColumnFormat::Currency->value,
            'net_pay' => ReportColumnFormat::Currency->value,
        ];
    }

    public function parameters(): array
    {
        return [
            ['key' => 'payroll_run_id', 'label' => 'Payroll Run', 'type' => 'payroll_run', 'required' => true],
            ['key' => 'group_by', 'label' => 'Group By', 'type' => 'group_by', 'required' => true],
            ['key' => 'format', 'label' => 'Export Format', 'type' => 'export_format', 'required' => false],
        ];
    }

    public function allowedFilterFields(): array
    {
        return ['payroll_run_id'];
    }

    public function allowedFilterOperators(): array
    {
        return ['='];
    }

    public function sortableFields(): array
    {
        return ['employee.employee_code'];
    }

    public function mandatoryColumnKeys(): array
    {
        return [
            'employee.employee_code',
            'employee.employee_name',
            'actual_gross',
            'payable_gross',
            'total_deductions',
            'net_pay',
        ];
    }

    public function allowedGroupByOptions(): array
    {
        return ['company', 'department', 'location'];
    }

    public function defaultConfig(): array
    {
        return [
            'columns' => array_keys($this->columns()),
            'filters' => [
                ['field' => 'payroll_run_id', 'op' => '=', 'param' => 'payroll_run_id'],
            ],
            'sort' => [
                ['field' => 'employee.employee_code', 'dir' => 'asc'],
            ],
            'parameters' => ['payroll_run_id', 'group_by', 'format'],
            'layout' => [
                'group_by' => 'company',
            ],
        ];
    }

    public function baseQuery(int $companyId): Builder
    {
        return EmployeePayroll::query()
            ->whereHas('payrollRun', fn (Builder $runQuery) => $runQuery->where('company_id', $companyId));
    }

    public function applyFilters(Builder $query, array $parameters, array $filterDefinitions): Builder
    {
        if (! empty($parameters['payroll_run_id'])) {
            $query->where('payroll_run_id', (int) $parameters['payroll_run_id']);
        }

        return $query;
    }

    public function applySorting(Builder $query, array $sortDefinitions): Builder
    {
        foreach ($sortDefinitions as $sort) {
            $field = (string) ($sort['field'] ?? '');
            $direction = strtolower((string) ($sort['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

            if ($field === 'employee.employee_code') {
                $query->join('employees', 'employees.id', '=', 'employee_payrolls.employee_id')
                    ->orderBy('employees.employee_code', $direction)
                    ->select('employee_payrolls.*');
            }
        }

        return $query;
    }

    public function eagerLoadsForColumns(array $columnKeys): array
    {
        return ['employee.department', 'employee.location', 'payrollRun'];
    }

    public function resolveValue(object $row, string $columnKey): mixed
    {
        return ReportValueResolver::resolve($row, $columnKey);
    }
}
