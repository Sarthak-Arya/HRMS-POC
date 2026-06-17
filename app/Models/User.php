<?php

namespace App\Models;

use App\Enums\Permission as PermissionEnum;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use HasRoles {
        hasRole as protected hasRoleViaSpatie;
    }

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function companiesHandled(): HasMany
    {
        return $this->hasMany(Company::class, 'company_handled_by');
    }

    public function hasRole($roles, ?string $guard = null): bool
    {
        if ($roles instanceof UserRole) {
            $roles = $roles->value;
        }

        return $this->hasRoleViaSpatie($roles, $guard);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    public function hasPermission(PermissionEnum|string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $name = $permission instanceof PermissionEnum ? $permission->value : $permission;

        return $this->hasPermissionTo($name);
    }

    public function hasAnyPermission(PermissionEnum|string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function canAccessCompany(Company|int $company): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($company instanceof Company) {
            return (int) $company->company_handled_by === $this->id;
        }

        return Company::query()
            ->whereKey($company)
            ->where('company_handled_by', $this->id)
            ->exists();
    }
}
