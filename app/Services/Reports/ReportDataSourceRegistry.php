<?php

namespace App\Services\Reports;

use App\Enums\Reports\ReportDataSourceKey;
use App\Services\Reports\DataSources\AttendanceMonthlyDataSource;
use App\Services\Reports\DataSources\EmployeesDataSource;
use App\Services\Reports\DataSources\PayrollRegisterDataSource;
use App\Services\Reports\DataSources\ReportDataSource;
use App\Services\Reports\DataSources\SalarySheetDataSource;
use InvalidArgumentException;

class ReportDataSourceRegistry
{
    /** @var array<string, ReportDataSource> */
    private array $sources;

    public function __construct()
    {
        $this->sources = [
            ReportDataSourceKey::Employees->value => new EmployeesDataSource(),
            ReportDataSourceKey::AttendanceMonthly->value => new AttendanceMonthlyDataSource(),
            ReportDataSourceKey::PayrollRegister->value => new PayrollRegisterDataSource(),
            ReportDataSourceKey::SalarySheet->value => new SalarySheetDataSource(),
        ];
    }

    public function get(string $key): ReportDataSource
    {
        if (! isset($this->sources[$key])) {
            throw new InvalidArgumentException("Unknown report data source [{$key}].");
        }

        return $this->sources[$key];
    }

    /**
     * @return list<ReportDataSource>
     */
    public function all(): array
    {
        return array_values($this->sources);
    }
}
