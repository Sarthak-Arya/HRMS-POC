<?php

namespace App\Enums;

enum Permission: string
{
    case CompaniesView = 'companies.view';
    case CompaniesCreate = 'companies.create';
    case UsersManage = 'users.manage';

    case DashboardView = 'dashboard.view';

    case EmployeesView = 'employees.view';
    case EmployeesCreate = 'employees.create';
    case EmployeesEdit = 'employees.edit';
    case EmployeesImport = 'employees.import';

    case AttendanceView = 'attendance.view';
    case AttendanceManage = 'attendance.manage';

    case CompensationView = 'compensation.view';
    case CompensationManage = 'compensation.manage';

    case SalaryGenerate = 'salary.generate';

    case AiAssistantUse = 'ai.assistant.use';

    public function label(): string
    {
        return match ($this) {
            self::CompaniesView => 'View companies',
            self::CompaniesCreate => 'Create companies',
            self::UsersManage => 'Manage users',
            self::DashboardView => 'View dashboard',
            self::EmployeesView => 'View employees',
            self::EmployeesCreate => 'Add employees',
            self::EmployeesEdit => 'Edit employees',
            self::EmployeesImport => 'Import employees',
            self::AttendanceView => 'View attendance',
            self::AttendanceManage => 'Manage attendance',
            self::CompensationView => 'View compensation',
            self::CompensationManage => 'Manage compensation',
            self::SalaryGenerate => 'Generate salary',
            self::AiAssistantUse => 'Use AI assistant',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::CompaniesView, self::CompaniesCreate, self::UsersManage => 'Organization',
            self::DashboardView => 'Dashboard',
            self::EmployeesView, self::EmployeesCreate, self::EmployeesEdit, self::EmployeesImport => 'Employees',
            self::AttendanceView, self::AttendanceManage => 'Attendance',
            self::CompensationView, self::CompensationManage => 'Compensation',
            self::SalaryGenerate => 'Payroll',
            self::AiAssistantUse => 'AI',
        };
    }
}
