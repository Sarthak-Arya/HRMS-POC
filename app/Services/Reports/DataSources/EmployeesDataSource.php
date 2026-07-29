<?php

namespace App\Services\Reports\DataSources;

use App\Enums\Reports\ReportColumnFormat;
use App\Enums\Reports\ReportDataSourceKey;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

class EmployeesDataSource implements ReportDataSource
{
    public function key(): ReportDataSourceKey
    {
        return ReportDataSourceKey::Employees;
    }

    public function label(): string
    {
        return ReportDataSourceKey::Employees->label();
    }

    public function columns(): array
    {
        return [
            'employee_code' => 'Employee Code',
            'employee_name' => 'Employee Name',
            'gender' => 'Gender',
            'father_name' => 'Father Name',
            'dob' => 'Date of Birth',
            'doj' => 'Joining Date',
            'dol' => 'Leaving Date',
            'pf_no' => 'PF Number',
            'esi_no' => 'ESI Number',
            'department.department_name' => 'Department',
            'designation.designation_name' => 'Designation',
            'location.name' => 'Location',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'employee_code' => ReportColumnFormat::Text->value,
            'employee_name' => ReportColumnFormat::Text->value,
            'gender' => ReportColumnFormat::Text->value,
            'father_name' => ReportColumnFormat::Text->value,
            'dob' => ReportColumnFormat::Date->value,
            'doj' => ReportColumnFormat::Date->value,
            'dol' => ReportColumnFormat::Date->value,
            'pf_no' => ReportColumnFormat::Text->value,
            'esi_no' => ReportColumnFormat::Text->value,
            'department.department_name' => ReportColumnFormat::Text->value,
            'designation.designation_name' => ReportColumnFormat::Text->value,
            'location.name' => ReportColumnFormat::Text->value,
        ];
    }

    public function parameters(): array
    {
        return [
            ['key' => 'department_id', 'label' => 'Department', 'type' => 'department', 'required' => false],
        ];
    }

    public function allowedFilterFields(): array
    {
        return ['department_id'];
    }

    public function allowedFilterOperators(): array
    {
        return ['=', 'in'];
    }

    public function sortableFields(): array
    {
        return ['employee_code', 'employee_name', 'doj', 'department.department_name'];
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
                ['field' => 'department_id', 'op' => '=', 'param' => 'department_id'],
            ],
            'sort' => [
                ['field' => 'employee_code', 'dir' => 'asc'],
            ],
            'parameters' => ['department_id'],
        ];
    }

    public function baseQuery(int $companyId): Builder
    {
        return Employee::query()->where('company_id', $companyId);
    }

    public function applyFilters(Builder $query, array $parameters, array $filterDefinitions): Builder
    {
        $activeParams = $this->activeFilterParams($filterDefinitions, $parameters);

        if (! empty($activeParams['department_id'])) {
            $query->where('department_id', (int) $activeParams['department_id']);
        }

        return $query;
    }

    public function applySorting(Builder $query, array $sortDefinitions): Builder
    {
        $applied = false;

        foreach ($sortDefinitions as $sort) {
            $field = (string) ($sort['field'] ?? '');
            $direction = strtolower((string) ($sort['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

            if (! in_array($field, $this->sortableFields(), true)) {
                continue;
            }

            if (in_array($field, ['employee_code', 'employee_name', 'doj'], true)) {
                $query->orderBy($field, $direction);
                $applied = true;
            } elseif ($field === 'department.department_name') {
                $query->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                    ->orderBy('departments.department_name', $direction)
                    ->select('employees.*');
                $applied = true;
            }
        }

        if (! $applied) {
            $query->orderBy('employee_code');
        }

        return $query;
    }

    public function eagerLoadsForColumns(array $columnKeys): array
    {
        $loads = [];

        foreach ($columnKeys as $column) {
            if (str_starts_with($column, 'department.')) {
                $loads[] = 'department';
            }
            if (str_starts_with($column, 'designation.')) {
                $loads[] = 'designation';
            }
            if (str_starts_with($column, 'location.')) {
                $loads[] = 'location';
            }
        }

        return array_values(array_unique($loads));
    }

    public function resolveValue(object $row, string $columnKey): mixed
    {
        return ReportValueResolver::resolve($row, $columnKey);
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
            $param = $filter['param'] ?? $filter['field'] ?? null;
            if ($param === null || $param === '') {
                continue;
            }
            if (array_key_exists($param, $parameters)) {
                $active[$param] = $parameters[$param];
            }
        }

        return $active;
    }
}
