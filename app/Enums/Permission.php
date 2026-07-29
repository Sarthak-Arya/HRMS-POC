<?php

namespace App\Enums;

enum Permission: string
{
    case CompaniesView = 'companies.view';
    case CompaniesCreate = 'companies.create';
    case CompaniesManageMultiple = 'companies.manage_multiple';
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

    case ReportsView = 'reports.view';
    case ReportsRun = 'reports.run';
    case ReportsManage = 'reports.manage';

    case AiAssistantUse = 'ai.assistant.use';

    case SettingsView = 'settings.view';
    case SettingsManageCompanyProfile = 'settings.manage.company_profile';
    case SettingsManageOrganization = 'settings.manage.organization';
    case SettingsManageAttendance = 'settings.manage.attendance';
    case SettingsManageCompensation = 'settings.manage.compensation';
    case SettingsManageReports = 'settings.manage.reports';
    case SettingsManageTax = 'settings.manage.tax';
    case SettingsManageStatutory = 'settings.manage.statutory';

    public function label(): string
    {
        return match ($this) {
            self::CompaniesView => 'View companies',
            self::CompaniesCreate => 'Create companies',
            self::CompaniesManageMultiple => 'Manage multiple companies',
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
            self::ReportsView => 'View reports',
            self::ReportsRun => 'Run and download reports',
            self::ReportsManage => 'Manage report templates',
            self::AiAssistantUse => 'Use AI assistant',
            self::SettingsView => 'View company settings',
            self::SettingsManageCompanyProfile => 'Manage company profile settings',
            self::SettingsManageOrganization => 'Manage organization settings',
            self::SettingsManageAttendance => 'Manage attendance settings',
            self::SettingsManageCompensation => 'Manage compensation settings',
            self::SettingsManageReports => 'Manage report settings',
            self::SettingsManageTax => 'Manage tax settings',
            self::SettingsManageStatutory => 'Manage statutory component settings',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::CompaniesView, self::CompaniesCreate, self::CompaniesManageMultiple, self::UsersManage => 'Organization',
            self::DashboardView => 'Dashboard',
            self::EmployeesView, self::EmployeesCreate, self::EmployeesEdit, self::EmployeesImport => 'Employees',
            self::AttendanceView, self::AttendanceManage => 'Attendance',
            self::CompensationView, self::CompensationManage => 'Compensation',
            self::SalaryGenerate => 'Payroll',
            self::ReportsView, self::ReportsRun, self::ReportsManage => 'Reports',
            self::AiAssistantUse => 'AI',
            self::SettingsView,
            self::SettingsManageCompanyProfile,
            self::SettingsManageOrganization,
            self::SettingsManageAttendance,
            self::SettingsManageCompensation,
            self::SettingsManageReports,
            self::SettingsManageTax,
            self::SettingsManageStatutory => 'Settings',
        };
    }
}
