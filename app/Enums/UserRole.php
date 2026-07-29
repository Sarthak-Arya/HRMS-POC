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
        ], true);
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
        ];
    }
}
