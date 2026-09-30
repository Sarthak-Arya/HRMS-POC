<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case B2bAdmin = 'b2b_admin';
    case B2bStaff = 'b2b_staff';
    case CompanyAdmin = 'company_admin';
    case PayrollManager = 'payroll_manager';
    case HrManager = 'hr_manager';
    case Accountant = 'accountant';
    case Viewer = 'viewer';
    case Manager = 'manager';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::B2bAdmin => 'B2B Firm Admin',
            self::B2bStaff => 'B2B Firm Staff',
            self::CompanyAdmin => 'Company Admin',
            self::PayrollManager => 'Payroll Manager',
            self::HrManager => 'HR Manager',
            self::Accountant => 'Accountant',
            self::Viewer => 'Viewer',
            self::Manager => 'People Manager',
            self::Employee => 'Employee',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Full platform access across all companies and features.',
            self::B2bAdmin => 'Owns a B2B firm — manages staff and all client companies under the firm.',
            self::B2bStaff => 'Works under a B2B firm — runs payroll and manages assigned client companies.',
            self::CompanyAdmin => 'Owns and manages a single company — employees, payroll, compensation, and attendance.',
            self::PayrollManager => 'Runs payroll end-to-end for an assigned company.',
            self::HrManager => 'Manages employees and attendance; read-only compensation.',
            self::Accountant => 'Manages compensation structures and salary generation.',
            self::Viewer => 'Read-only access to company dashboards and records.',
            self::Manager => 'Employee self-service plus approvals for direct reports.',
            self::Employee => 'Employee self-service — profile, leave, attendance, and payslips.',
        };
    }

    public function isB2b(): bool
    {
        return in_array($this, [self::B2bAdmin, self::B2bStaff], true);
    }

    public function isB2c(): bool
    {
        return in_array($this, [
            self::CompanyAdmin,
            self::PayrollManager,
            self::HrManager,
            self::Accountant,
            self::Viewer,
            self::Manager,
            self::Employee,
        ], true);
    }

    public function isSelfService(): bool
    {
        return in_array($this, [self::Employee, self::Manager], true);
    }

    /**
     * Roles a user may choose during self-registration (platform admin is excluded).
     *
     * @return list<self>
     */
    public static function selfRegisterable(): array
    {
        return [
            self::B2bAdmin,
            self::CompanyAdmin,
            self::PayrollManager,
            self::HrManager,
            self::Accountant,
            self::Viewer,
        ];
    }

    /**
     * @return list<self>
     */
    public static function b2bRoles(): array
    {
        return [
            self::B2bAdmin,
            self::B2bStaff,
        ];
    }

    /**
     * @return list<self>
     */
    public static function b2cRoles(): array
    {
        return [
            self::CompanyAdmin,
            self::PayrollManager,
            self::HrManager,
            self::Accountant,
            self::Viewer,
            self::Manager,
            self::Employee,
        ];
    }
}
