<?php

namespace App\Services\Ess;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EmployeeContext
{
    public function forUser(User $user, int|string $companyId): Employee
    {
        $employee = Employee::query()
            ->with(['department', 'designation', 'location', 'manager', 'company'])
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->whereNull('dol')
            ->first();

        if (! $employee) {
            throw (new ModelNotFoundException)->setModel(Employee::class);
        }

        return $employee;
    }

    public function findForUser(User $user, int|string $companyId): ?Employee
    {
        try {
            return $this->forUser($user, $companyId);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    public function linkedEmployee(User $user): ?Employee
    {
        return Employee::query()
            ->with(['company'])
            ->where('user_id', $user->id)
            ->whereNull('dol')
            ->first();
    }
}
