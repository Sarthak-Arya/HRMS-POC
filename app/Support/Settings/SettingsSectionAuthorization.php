<?php

namespace App\Support\Settings;

use App\Enums\Permission;
use App\Enums\Settings\CompanySettingsSection;
use App\Models\User;

class SettingsSectionAuthorization
{
    public static function viewPermission(): Permission
    {
        return Permission::SettingsView;
    }

    public static function managePermissionFor(CompanySettingsSection $section): Permission
    {
        return match ($section) {
            CompanySettingsSection::CompanyProfile => Permission::SettingsManageCompanyProfile,
            CompanySettingsSection::Organization => Permission::SettingsManageOrganization,
            CompanySettingsSection::Attendance => Permission::SettingsManageAttendance,
            CompanySettingsSection::Compensation => Permission::SettingsManageCompensation,
            CompanySettingsSection::Reports => Permission::SettingsManageReports,
            CompanySettingsSection::Tax => Permission::SettingsManageTax,
            CompanySettingsSection::Statutory => Permission::SettingsManageStatutory,
        };
    }

    public static function canView(?User $user): bool
    {
        return $user !== null && $user->hasPermission(self::viewPermission());
    }

    public static function canManageSection(?User $user, CompanySettingsSection $section): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasPermission(self::managePermissionFor($section));
    }

    /**
     * @return list<CompanySettingsSection>
     */
    public static function manageableSections(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return array_values(array_filter(
            CompanySettingsSection::cases(),
            fn (CompanySettingsSection $section) => self::canManageSection($user, $section),
        ));
    }
}
