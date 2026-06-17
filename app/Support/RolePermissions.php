<?php

namespace App\Support;

use App\Enums\Permission;
use App\Enums\UserRole;

class RolePermissions
{
    /**
     * @return list<Permission>
     */
    public static function forRole(UserRole $role): array
    {
        return match ($role) {
            UserRole::Admin => Permission::cases(),

            UserRole::CompanyAdmin, UserRole::PayrollManager => [
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
                Permission::AiAssistantUse,
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
                Permission::AiAssistantUse,
            ],

            UserRole::Accountant => [
                Permission::CompaniesView,
                Permission::DashboardView,
                Permission::EmployeesView,
                Permission::AttendanceView,
                Permission::CompensationView,
                Permission::CompensationManage,
                Permission::SalaryGenerate,
            ],

            UserRole::Viewer => [
                Permission::CompaniesView,
                Permission::DashboardView,
                Permission::EmployeesView,
                Permission::AttendanceView,
                Permission::CompensationView,
            ],
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
