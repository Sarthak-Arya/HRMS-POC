<?php

namespace App\Services\Ess;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use App\Services\Auth\RolePermissionSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmployeePortalAccountService
{
    /**
     * Create or link a portal login for an employee.
     *
     * @return array{user: User, temporary_password: string|null, created: bool}
     */
    public function invite(
        Employee $employee,
        string $email,
        UserRole $role = UserRole::Employee,
        ?string $password = null,
    ): array {
        if (! in_array($role, [UserRole::Employee, UserRole::Manager], true)) {
            throw ValidationException::withMessages([
                'role' => 'Portal accounts must use the employee or manager role.',
            ]);
        }

        $email = strtolower(trim($email));

        if ($email === '') {
            throw ValidationException::withMessages([
                'email' => 'A work email is required to invite an employee to the portal.',
            ]);
        }

        return DB::transaction(function () use ($employee, $email, $role, $password) {
            $employee->refresh();

            if ($employee->user_id) {
                $existing = User::query()->findOrFail($employee->user_id);
                RolePermissionSync::syncRole($role);
                $existing->syncRoles([$role->value]);
                $employee->forceFill([
                    'work_email' => $employee->work_email ?: $email,
                ])->save();

                return [
                    'user' => $existing,
                    'temporary_password' => null,
                    'created' => false,
                ];
            }

            $linkedElsewhere = Employee::query()
                ->where('user_id', '!=', null)
                ->whereHas('user', fn ($q) => $q->where('email', $email))
                ->where('id', '!=', $employee->id)
                ->exists();

            if ($linkedElsewhere) {
                throw ValidationException::withMessages([
                    'email' => 'That email is already linked to another employee portal account.',
                ]);
            }

            $temporaryPassword = $password ?: Str::password(12);
            $user = User::query()->where('email', $email)->first();
            $created = false;

            if ($user) {
                if (Employee::query()->where('user_id', $user->id)->where('id', '!=', $employee->id)->exists()) {
                    throw ValidationException::withMessages([
                        'email' => 'That user is already linked to another employee.',
                    ]);
                }
            } else {
                $user = User::query()->create([
                    'name' => $employee->employee_name,
                    'email' => $email,
                    'password' => Hash::make($temporaryPassword),
                    'company_id' => $employee->company_id,
                ]);
                $created = true;
            }

            RolePermissionSync::syncRole($role);
            $user->syncRoles([$role->value]);
            $user->forceFill(['company_id' => $employee->company_id])->save();

            $employee->forceFill([
                'user_id' => $user->id,
                'work_email' => $email,
            ])->save();

            return [
                'user' => $user->fresh(),
                'temporary_password' => $created ? $temporaryPassword : null,
                'created' => $created,
            ];
        });
    }

    public function canInvite(User $actor): bool
    {
        return $actor->hasPermission(Permission::EssPortalInvite)
            || $actor->hasPermission(Permission::EmployeesEdit);
    }
}
