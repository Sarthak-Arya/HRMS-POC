<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case CompanyAdmin = 'company_admin';
    case PayrollManager = 'payroll_manager';
    case HrManager = 'hr_manager';
    case Accountant = 'accountant';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::CompanyAdmin => 'Company Admin',
            self::PayrollManager => 'Payroll Manager',
            self::HrManager => 'HR Manager',
            self::Accountant => 'Accountant',
            self::Viewer => 'Viewer',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Full platform access across all companies and features.',
            self::CompanyAdmin => 'Owns and manages a company — employees, payroll, compensation, and attendance.',
            self::PayrollManager => 'Runs payroll end-to-end for assigned companies.',
            self::HrManager => 'Manages employees and attendance; read-only compensation.',
            self::Accountant => 'Manages compensation structures and salary generation.',
            self::Viewer => 'Read-only access to company dashboards and records.',
        };
    }

    /**
     * Roles a user may choose during self-registration (platform admin is excluded).
     *
     * @return list<self>
     */
    public static function selfRegisterable(): array
    {
        return [
            self::CompanyAdmin,
            self::PayrollManager,
            self::HrManager,
            self::Accountant,
            self::Viewer,
        ];
    }
}
