<?php

namespace App\Services\Reports\DataSources;

use App\Enums\Reports\ReportColumnFormat;
use App\Enums\Reports\ReportDataSourceKey;
use App\Models\EmployeeAttendanceSummary;
use Illuminate\Database\Eloquent\Builder;

class AttendanceMonthlyDataSource implements ReportDataSource
{
    public function key(): ReportDataSourceKey
    {
        return ReportDataSourceKey::AttendanceMonthly;
    }

    public function label(): string
    {
        return ReportDataSourceKey::AttendanceMonthly->label();
    }

    public function columns(): array
    {
        return [
            'employee.employee_code' => 'Employee Code',
            'employee.employee_name' => 'Employee Name',
            'employee.department.department_name' => 'Department',
            'month' => 'Month',
            'year' => 'Year',
            'working_days' => 'Working Days',
            'present_days' => 'Present Days',
            'half_days' => 'Half Days',
            'paid_leave_days' => 'Paid Leave Days',
            'lop_days' => 'LOP Days',
            'holiday_days' => 'Holiday Days',
            'weekly_off_days' => 'Weekly Off Days',
            'overtime_hours' => 'Overtime Hours',
            'worked_days' => 'Worked Days',
            'total_days' => 'Total Days',
        ];
    }

    public function columnFormats(): array
    {
        $formats = [];
        foreach (array_keys($this->columns()) as $key) {
            $formats[$key] = in_array($key, ['employee.employee_code', 'employee.employee_name', 'employee.department.department_name'], true)
                ? ReportColumnFormat::Text->value
                : ReportColumnFormat::Number->value;
        }

        return $formats;
    }

    public function parameters(): array
    {
        return [
            ['key' => 'month', 'label' => 'Month', 'type' => 'month', 'required' => true],
            ['key' => 'year', 'label' => 'Year', 'type' => 'year', 'required' => true],
            ['key' => 'department_id', 'label' => 'Department', 'type' => 'department', 'required' => false],
        ];
    }

    public function allowedFilterFields(): array
    {
        return ['month', 'year', 'employee.department_id'];
    }

    public function allowedFilterOperators(): array
    {
        return ['=', 'in'];
    }

    public function sortableFields(): array
    {
        return ['employee.employee_code', 'employee.employee_name', 'month', 'year', 'present_days', 'lop_days'];
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
                ['field' => 'month', 'op' => '=', 'param' => 'month'],
                ['field' => 'year', 'op' => '=', 'param' => 'year'],
                ['field' => 'employee.department_id', 'op' => '=', 'param' => 'department_id'],
            ],
            'sort' => [
                ['field' => 'employee.employee_code', 'dir' => 'asc'],
            ],
            'parameters' => ['month', 'year', 'department_id'],
        ];
    }

    public function baseQuery(int $companyId): Builder
    {
        return EmployeeAttendanceSummary::query()
            ->where('employee_attendance_summaries.company_id', $companyId);
    }

    public function applyFilters(Builder $query, array $parameters, array $filterDefinitions): Builder
    {
        $activeParams = $this->activeFilterParams($filterDefinitions, $parameters);

        if (isset($activeParams['month'])) {
            $query->where('month', (int) $activeParams['month']);
        }

        if (isset($activeParams['year'])) {
            $query->where('year', (int) $activeParams['year']);
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
                    $query->join('employees', 'employees.id', '=', 'employee_attendance_summaries.employee_id')
                        ->select('employee_attendance_summaries.*');
                    $joinedEmployees = true;
                }
                $column = $field === 'employee.employee_code' ? 'employees.employee_code' : 'employees.employee_name';
                $query->orderBy($column, $direction);
                $applied = true;
            } elseif (in_array($field, ['month', 'year', 'present_days', 'lop_days'], true)) {
                $query->orderBy($field, $direction);
                $applied = true;
            }
        }

        if (! $applied) {
            if (! $joinedEmployees) {
                $query->join('employees', 'employees.id', '=', 'employee_attendance_summaries.employee_id')
                    ->select('employee_attendance_summaries.*');
            }
            $query->orderBy('employees.employee_code');
        }

        return $query;
    }

    public function eagerLoadsForColumns(array $columnKeys): array
    {
        $loads = ['employee.department'];

        foreach ($columnKeys as $column) {
            if (str_starts_with($column, 'employee.')) {
                $loads[] = 'employee';
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
