<?php

namespace App\Enums\Settings;

enum CompanySettingsSection: string
{
    case CompanyProfile = 'companyProfile';
    case Organization = 'organization';
    case Attendance = 'attendance';
    case Compensation = 'compensation';
    case Reports = 'reports';
    case Tax = 'tax';
    case Statutory = 'statutory';

    public function label(): string
    {
        return match ($this) {
            self::CompanyProfile => 'Company Profile',
            self::Organization => 'Organization',
            self::Attendance => 'Attendance',
            self::Compensation => 'Compensation',
            self::Reports => 'Reports',
            self::Tax => 'Tax',
            self::Statutory => 'Statutory Components',
        };
    }

    public function tabKey(): string
    {
        return match ($this) {
            self::CompanyProfile => 'company_profile',
            self::Organization => 'organization',
            self::Attendance => 'attendance',
            self::Compensation => 'compensation',
            self::Reports => 'reports',
            self::Tax => 'tax',
            self::Statutory => 'statutory',
        };
    }

    public static function fromTabKey(string $tab): ?self
    {
        return match ($tab) {
            'company_profile' => self::CompanyProfile,
            'organization' => self::Organization,
            'attendance' => self::Attendance,
            'compensation' => self::Compensation,
            'reports' => self::Reports,
            'tax' => self::Tax,
            'statutory', 'statutory_components' => self::Statutory,
            default => null,
        };
    }
}
