<?php

namespace App\Enums\Reports;

enum ReportDataSourceKey: string
{
    case Employees = 'employees';
    case AttendanceMonthly = 'attendance_monthly';
    case PayrollRegister = 'payroll_register';
    case SalarySheet = 'salary_sheet';

    public function label(): string
    {
        return match ($this) {
            self::Employees => 'Employee Master',
            self::AttendanceMonthly => 'Monthly Attendance Register',
            self::PayrollRegister => 'Payroll Register',
            self::SalarySheet => 'Monthly Salary Sheet',
        };
    }
}
