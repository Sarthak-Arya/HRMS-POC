<?php

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserRoleService
{
    public static function defaultRole(): ?Role
    {
        return self::findBySlug(UserRole::CompanyAdmin);
    }

    public static function findBySlug(UserRole|string $role): ?Role
    {
        $slug = $role instanceof UserRole ? $role->value : $role;

        return Role::query()
            ->where('name', $slug)
            ->where('guard_name', 'web')
            ->first();
    }

    /**
     * @return list<Role>
     */
    public static function selfRegisterableRoles(): array
    {
        $names = array_map(
            fn (UserRole $role) => $role->value,
            UserRole::selfRegisterable(),
        );

        return Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $names)
            ->orderByRaw('FIELD(name, ' . implode(',', array_fill(0, count($names), '?')) . ')', $names)
            ->get()
            ->all();
    }

    public static function ensureDefaultRole(User $user): User
    {
        if ($user->roles()->exists()) {
            return $user;
        }

        $role = self::defaultRole();

        if ($role) {
            $user->assignRole($role);
        }

        return $user->fresh();
    }
}
