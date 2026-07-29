<?php

namespace App\Services\Reports\DataSources;

use App\Enums\Reports\ReportColumnFormat;
use App\Enums\Reports\ReportDataSourceKey;
use App\Models\EmployeePayroll;
use Illuminate\Database\Eloquent\Builder;

class PayrollRegisterDataSource implements ReportDataSource
{
    public function key(): ReportDataSourceKey
    {
        return ReportDataSourceKey::PayrollRegister;
    }

    public function label(): string
    {
        return ReportDataSourceKey::PayrollRegister->label();
    }

    public function columns(): array
    {
        return [
            'employee.employee_code' => 'Employee Code',
            'employee.employee_name' => 'Employee Name',
            'employee.department.department_name' => 'Department',
            'payrollRun.month' => 'Payroll Month',
            'payrollRun.year' => 'Payroll Year',
            'gross_earnings' => 'Gross Earnings',
            'gross_deductions' => 'Gross Deductions',
            'employer_contributions' => 'Employer Contributions',
            'net_pay' => 'Net Pay',
            'status' => 'Status',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'employee.employee_code' => ReportColumnFormat::Text->value,
            'employee.employee_name' => ReportColumnFormat::Text->value,
            'employee.department.department_name' => ReportColumnFormat::Text->value,
            'payrollRun.month' => ReportColumnFormat::Number->value,
            'payrollRun.year' => ReportColumnFormat::Number->value,
            'gross_earnings' => ReportColumnFormat::Currency->value,
            'gross_deductions' => ReportColumnFormat::Currency->value,
            'employer_contributions' => ReportColumnFormat::Currency->value,
            'net_pay' => ReportColumnFormat::Currency->value,
            'status' => ReportColumnFormat::Text->value,
        ];
    }

    public function parameters(): array
    {
        return [
            ['key' => 'payroll_run_id', 'label' => 'Payroll Run', 'type' => 'payroll_run', 'required' => true],
            ['key' => 'department_id', 'label' => 'Department', 'type' => 'department', 'required' => false],
        ];
    }

    public function allowedFilterFields(): array
    {
        return ['payroll_run_id', 'employee.department_id'];
    }

    public function allowedFilterOperators(): array
    {
        return ['=', 'in'];
    }

    public function sortableFields(): array
    {
        return ['employee.employee_code', 'employee.employee_name', 'net_pay', 'gross_earnings', 'status'];
    }

    public function mandatoryColumnKeys(): array
    {
        return [];
    }

    public function allowedGroupByOptions(): array
    {
        return [];
    }

    public function defaultConfig(): array
    {
        return [
            'columns' => array_keys($this->columns()),
            'filters' => [
                ['field' => 'payroll_run_id', 'op' => '=', 'param' => 'payroll_run_id'],
                ['field' => 'employee.department_id', 'op' => '=', 'param' => 'department_id'],
            ],
            'sort' => [
                ['field' => 'employee.employee_code', 'dir' => 'asc'],
            ],
            'parameters' => ['payroll_run_id', 'department_id'],
        ];
    }

    public function baseQuery(int $companyId): Builder
    {
        return EmployeePayroll::query()
            ->whereHas('payrollRun', fn (Builder $runQuery) => $runQuery->where('company_id', $companyId));
    }

    public function applyFilters(Builder $query, array $parameters, array $filterDefinitions): Builder
    {
        $activeParams = $this->activeFilterParams($filterDefinitions, $parameters);

        if (! empty($activeParams['payroll_run_id'])) {
            $query->where('payroll_run_id', (int) $activeParams['payroll_run_id']);
        }

        if (! empty($activeParams['department_id'])) {
            $departmentId = (int) $activeParams['department_id'];
            $query->whereHas('employee', fn (Builder $employeeQuery) => $employeeQuery->where('department_id', $departmentId));
        }

        return $query;
    }

    public function applySorting(Builder $query, array $sortDefinitions): Builder
    {
        $applied = false;
        $joinedEmployees = false;

        foreach ($sortDefinitions as $sort) {
            $field = (string) ($sort['field'] ?? '');
            $direction = strtolower((string) ($sort['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

            if (! in_array($field, $this->sortableFields(), true)) {
                continue;
            }

            if (in_array($field, ['employee.employee_code', 'employee.employee_name'], true)) {
                if (! $joinedEmployees) {
                    $query->join('employees', 'employees.id', '=', 'employee_payrolls.employee_id')
                        ->select('employee_payrolls.*');
                    $joinedEmployees = true;
                }
                $column = $field === 'employee.employee_code' ? 'employees.employee_code' : 'employees.employee_name';
                $query->orderBy($column, $direction);
                $applied = true;
            } elseif (in_array($field, ['net_pay', 'gross_earnings', 'status'], true)) {
                $query->orderBy($field, $direction);
                $applied = true;
            }
        }

        if (! $applied) {
            if (! $joinedEmployees) {
                $query->join('employees', 'employees.id', '=', 'employee_payrolls.employee_id')
                    ->select('employee_payrolls.*');
            }
            $query->orderBy('employees.employee_code');
        }

        return $query;
    }

    public function eagerLoadsForColumns(array $columnKeys): array
    {
        return ['employee.department', 'payrollRun'];
    }

    public function resolveValue(object $row, string $columnKey): mixed
    {
        $value = ReportValueResolver::resolve($row, $columnKey);

        if ($columnKey === 'status' && $value instanceof \BackedEnum) {
            return $value->value;
        }

        return $value;
    }

    /**
     * @param  list<array<string, mixed>>  $filterDefinitions
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function activeFilterParams(array $filterDefinitions, array $parameters): array
    {
        if ($filterDefinitions === []) {
            return $parameters;
        }

        $active = [];
        foreach ($filterDefinitions as $filter) {
            $param = $filter['param'] ?? null;
            if ($param === null || $param === '') {
                $field = (string) ($filter['field'] ?? '');
                $param = match ($field) {
                    'employee.department_id' => 'department_id',
                    default => $field,
                };
            }
            if ($param !== '' && array_key_exists($param, $parameters)) {
                $active[$param] = $parameters[$param];
            }
        }

        return $active;
    }
}
