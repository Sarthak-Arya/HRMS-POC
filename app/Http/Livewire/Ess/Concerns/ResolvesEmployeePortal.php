<?php

namespace App\Http\Livewire\Ess\Concerns;

use App\Models\Employee;
use App\Services\Ess\EmployeeContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait ResolvesEmployeePortal
{
    public string $companyId = '';

    public int $employeeId = 0;

    protected function bootEmployeePortal(?string $companyId = null): Employee
    {
        $this->companyId = (string) ($companyId
            ?? request()->route('company_id')
            ?? request()->session()->get('companyId', '')
            ?? request()->session()->get('company_id', ''));

        if ($this->companyId !== '') {
            request()->session()->put('companyId', $this->companyId);
            request()->session()->put('company_id', $this->companyId);
        }

        $fromRequest = request()->attributes->get('ess.employee');
        if ($fromRequest instanceof Employee) {
            $this->employeeId = (int) $fromRequest->id;

            return $fromRequest;
        }

        $user = auth()->user();
        abort_unless($user, 401);

        try {
            $employee = app(EmployeeContext::class)->forUser($user, $this->companyId);
        } catch (ModelNotFoundException) {
            abort(403, 'Your login is not linked to an active employee record for this company.');
        }

        $this->employeeId = (int) $employee->id;

        return $employee;
    }
}
