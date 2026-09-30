<?php

namespace App\Support;

use App\Enums\Permission;
use App\Enums\UserRole;

class RolePermissions
{
    /**
     * @return list<Permission>
     */
    public static function selfServiceBundle(): array
    {
        return [
            Permission::EssAccess,
            Permission::EssProfile,
            Permission::EssLeave,
            Permission::EssAttendance,
            Permission::EssPayslips,
            Permission::EssDirectory,
        ];
    }

    /**
     * @return list<Permission>
     */
    public static function forRole(UserRole $role): array
    {
        return match ($role) {
            UserRole::Admin => Permission::cases(),

            UserRole::B2bAdmin => [
                Permission::CompaniesView,
                Permission::CompaniesCreate,
                Permission::CompaniesManageMultiple,
                Permission::UsersManage,
                Permission::DashboardView,
                Permission::EmployeesView,
                Permission::EmployeesCreate,
                Permission::EmployeesEdit,
                Permission::EmployeesImport,
                Permission::AttendanceView,
                Permission::AttendanceManage,
                Permission::CompensationView,
                Permission::CompensationManage,
                Permission::SalaryGenerate,
                Permission::ReportsView,
                Permission::ReportsRun,
                Permission::ReportsManage,
                Permission::AiAssistantUse,
                Permission::SettingsView,
                Permission::SettingsManageCompanyProfile,
                Permission::SettingsManageOrganization,
                Permission::SettingsManageAttendance,
                Permission::SettingsManageCompensation,
                Permission::SettingsManageReports,
                Permission::SettingsManageTax,
                Permission::SettingsManageStatutory,
                Permission::EssApprovalsAny,
                Permission::EssPortalInvite,
            ],

            UserRole::B2bStaff => [
                Permission::CompaniesView,
                Permission::CompaniesManageMultiple,
                Permission::DashboardView,
                Permission::EmployeesView,
                Permission::EmployeesCreate,
                Permission::EmployeesEdit,
                Permission::EmployeesImport,
                Permission::AttendanceView,
                Permission::AttendanceManage,
                Permission::CompensationView,
                Permission::CompensationManage,
                Permission::SalaryGenerate,
                Permission::ReportsView,
                Permission::ReportsRun,
                Permission::AiAssistantUse,
                Permission::SettingsView,
                Permission::SettingsManageCompensation,
                Permission::EssApprovalsAny,
                Permission::EssPortalInvite,
            ],

            UserRole::CompanyAdmin => [
                Permission::CompaniesView,
                Permission::CompaniesCreate,
                Permission::DashboardView,
                Permission::EmployeesView,
                Permission::EmployeesCreate,
                Permission::EmployeesEdit,
                Permission::EmployeesImport,
                Permission::AttendanceView,
                Permission::AttendanceManage,
                Permission::CompensationView,
                Permission::CompensationManage,
                Permission::SalaryGenerate,
                Permission::ReportsView,
                Permission::ReportsRun,
                Permission::ReportsManage,
                Permission::AiAssistantUse,
                Permission::SettingsView,
                Permission::SettingsManageCompanyProfile,
                Permission::SettingsManageOrganization,
                Permission::SettingsManageAttendance,
                Permission::SettingsManageCompensation,
                Permission::SettingsManageReports,
                Permission::SettingsManageTax,
                Permission::SettingsManageStatutory,
                Permission::EssApprovalsAny,
                Permission::EssPortalInvite,
            ],

            UserRole::PayrollManager => [
                Permission::CompaniesView,
                Permission::DashboardView,
                Permission::EmployeesView,
                Permission::EmployeesCreate,
                Permission::EmployeesEdit,
                Permission::EmployeesImport,
                Permission::AttendanceView,
                Permission::AttendanceManage,
                Permission::CompensationView,
                Permission::CompensationManage,
                Permission::SalaryGenerate,
                Permission::ReportsView,
                Permission::ReportsRun,
                Permission::AiAssistantUse,
                Permission::SettingsView,
                Permission::SettingsManageCompensation,
                Permission::SettingsManageTax,
                Permission::SettingsManageStatutory,
                Permission::EssPortalInvite,
            ],

            UserRole::HrManager => [
                Permission::CompaniesView,
                Permission::DashboardView,
                Permission::EmployeesView,
                Permission::EmployeesCreate,
                Permission::EmployeesEdit,
                Permission::EmployeesImport,
                Permission::AttendanceView,
                Permission::AttendanceManage,
                Permission::CompensationView,
                Permission::ReportsView,
                Permission::ReportsRun,
                Permission::AiAssistantUse,
                Permission::SettingsView,
                Permission::SettingsManageOrganization,
                Permission::SettingsManageAttendance,
                Permission::EssApprovals,
                Permission::EssApprovalsAny,
                Permission::EssPortalInvite,
            ],

            UserRole::Accountant => [
                Permission::CompaniesView,
                Permission::DashboardView,
                Permission::EmployeesView,
                Permission::AttendanceView,
                Permission::CompensationView,
                Permission::CompensationManage,
                Permission::SalaryGenerate,
                Permission::ReportsView,
                Permission::ReportsRun,
                Permission::SettingsView,
                Permission::SettingsManageTax,
                Permission::SettingsManageStatutory,
            ],

            UserRole::Viewer => [
                Permission::CompaniesView,
                Permission::DashboardView,
                Permission::EmployeesView,
                Permission::AttendanceView,
                Permission::CompensationView,
                Permission::ReportsView,
            ],

            UserRole::Manager => [
                ...self::selfServiceBundle(),
                Permission::EssApprovals,
            ],

            UserRole::Employee => self::selfServiceBundle(),
        };
    }

    /**
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        $matrix = [];

        foreach (UserRole::cases() as $role) {
            $matrix[$role->value] = array_map(
                fn (Permission $permission) => $permission->value,
                self::forRole($role),
            );
        }

        return $matrix;
    }
}
