<?php

namespace App\Services\Auth;

use App\Enums\Permission as PermissionEnum;
use App\Enums\UserRole;
use App\Support\RolePermissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSync
{
    public static function ensurePermissionsExist(): void
    {
        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }
    }

    public static function syncRole(Role|UserRole|string $role): void
    {
        self::ensurePermissionsExist();

        $slug = $role instanceof Role
            ? $role->name
            : ($role instanceof UserRole ? $role->value : $role);

        $roleModel = $role instanceof Role
            ? $role
            : Role::query()->where('name', $slug)->where('guard_name', 'web')->first();

        $roleEnum = UserRole::tryFrom($slug);

        if (!$roleModel || !$roleEnum) {
            return;
        }

        $permissionNames = array_map(
            fn (PermissionEnum $permission) => $permission->value,
            RolePermissions::forRole($roleEnum),
        );

        $roleModel->syncPermissions($permissionNames);
    }

    public static function syncAllRoles(): void
    {
        self::ensurePermissionsExist();

        foreach (UserRole::cases() as $roleEnum) {
            self::syncRole($roleEnum);
        }
    }
}
