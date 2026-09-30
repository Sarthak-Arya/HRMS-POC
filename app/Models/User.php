<?php

namespace App\Models;

use App\Enums\Permission as PermissionEnum;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function b2bFirm(): BelongsTo
    {
        return $this->belongsTo(B2bFirm::class, 'b2b_firm_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function employee(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Companies this B2B user can manage through their firm.
     */
    public function firmCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'b2b_firm_id', 'b2b_firm_id');
    }

    public function isSelfServiceUser(): bool
    {
        return $this->hasRole(UserRole::Employee) || $this->hasRole(UserRole::Manager);
    }

    public function isB2bUser(): bool
    {
        return $this->b2b_firm_id !== null;
    }

    public function isB2cUser(): bool
    {
        return $this->company_id !== null && $this->b2b_firm_id === null;
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

        $companyModel = $company instanceof Company
            ? $company
            : Company::query()->find($company);

        if (! $companyModel) {
            return false;
        }

        if ($this->b2b_firm_id !== null) {
            return (int) $companyModel->b2b_firm_id === (int) $this->b2b_firm_id;
        }

        if ($this->company_id !== null) {
            return (int) $companyModel->id === (int) $this->company_id;
        }

        return false;
    }
}
