<?php

namespace App\Http\Livewire;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Services\Ess\EmployeePortalAccountService;
use Livewire\Component;

class ViewEmployeeDetails extends Component
{
    public string $companyId = '';

    public string $employeeId = '';

    public string $inviteEmail = '';

    public string $inviteRole = 'employee';

    public string $managerId = '';

    public string $inviteFlash = '';

    public string $inviteFlashType = 'success';

    public ?string $tempPassword = null;

    public function mount(?string $company_id = null, ?string $employee_id = null): void
    {
        $this->companyId = $company_id ?? (string) session()->get('companyId', '');
        $this->employeeId = $employee_id ?? '';

        if ($this->companyId !== '') {
            session()->put('companyId', $this->companyId);
        }
    }

    public function inviteToPortal(EmployeePortalAccountService $portalAccounts): void
    {
        $this->validate([
            'inviteEmail' => 'required|email|max:191',
            'inviteRole' => 'required|in:employee,manager',
        ]);

        $employee = Employee::query()
            ->where('company_id', $this->companyId)
            ->findOrFail($this->employeeId);

        abort_unless($portalAccounts->canInvite(auth()->user()), 403);

        try {
            $result = $portalAccounts->invite(
                $employee,
                $this->inviteEmail,
                UserRole::from($this->inviteRole),
            );

            $this->tempPassword = $result['temporary_password'];
            $this->inviteFlash = $result['created']
                ? 'Portal account created. Share the temporary password securely.'
                : 'Existing user linked and portal role updated.';
            $this->inviteFlashType = 'success';
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->inviteFlash = collect($e->errors())->flatten()->first() ?? 'Invite failed.';
            $this->inviteFlashType = 'error';
            $this->tempPassword = null;
        }
    }

    public function saveManager(): void
    {
        $employee = Employee::query()
            ->where('company_id', $this->companyId)
            ->findOrFail($this->employeeId);

        $managerId = $this->managerId !== '' ? (int) $this->managerId : null;

        if ($managerId !== null && $managerId === (int) $employee->id) {
            $this->addError('managerId', 'An employee cannot manage themselves.');

            return;
        }

        if ($managerId !== null) {
            $exists = Employee::query()
                ->where('company_id', $this->companyId)
                ->whereKey($managerId)
                ->exists();

            if (! $exists) {
                $this->addError('managerId', 'Selected manager was not found.');

                return;
            }
        }

        $employee->forceFill(['manager_id' => $managerId])->save();
        $this->inviteFlash = 'Reporting manager updated.';
        $this->inviteFlashType = 'success';
    }

    public function render()
    {
        $employee = null;
        $managers = collect();

        if ($this->companyId !== '' && $this->employeeId !== '') {
            $employee = Employee::with(['department', 'designation', 'location', 'manager', 'user'])
                ->where('company_id', $this->companyId)
                ->find($this->employeeId);

            if ($employee) {
                if ($this->inviteEmail === '' && $employee->work_email) {
                    $this->inviteEmail = (string) $employee->work_email;
                }
                if ($this->managerId === '' && $employee->manager_id) {
                    $this->managerId = (string) $employee->manager_id;
                }
            }

            $managers = Employee::query()
                ->where('company_id', $this->companyId)
                ->whereNull('dol')
                ->where('id', '!=', $this->employeeId)
                ->orderBy('employee_name')
                ->get(['id', 'employee_name', 'employee_code']);
        }

        return view('livewire.view-employee-details', [
            'employee' => $employee,
            'companyId' => $this->companyId,
            'managers' => $managers,
        ]);
    }
}
