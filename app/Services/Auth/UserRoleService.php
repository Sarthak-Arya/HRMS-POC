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
     * Ensure every self-registerable role exists in the database (find-or-create).
     * Signup depends on Spatie Role rows; an unseeded DB would otherwise show an empty dropdown.
     */
    public static function ensureSelfRegisterableRolesExist(): void
    {
        foreach (UserRole::selfRegisterable() as $role) {
            Role::findOrCreate($role->value, 'web');
        }
    }

    /**
     * @return list<Role>
     */
    public static function selfRegisterableRoles(): array
    {
        self::ensureSelfRegisterableRolesExist();

        $names = array_map(
            fn (UserRole $role) => $role->value,
            UserRole::selfRegisterable(),
        );

        $rolesByName = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $names)
            ->get()
            ->keyBy('name');

        $ordered = [];
        foreach ($names as $name) {
            if ($rolesByName->has($name)) {
                $ordered[] = $rolesByName->get($name);
            }
        }

        return $ordered;
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
