<?php

namespace App\Http\Livewire\Ess;

use App\Http\Livewire\Ess\Concerns\ResolvesEmployeePortal;
use App\Services\Ess\EmployeeSelfServiceProfileService;
use Livewire\Component;

class MyProfile extends Component
{
    use ResolvesEmployeePortal;

    public string $employeeName = '';

    public string $employeeCode = '';

    public string $department = '';

    public string $designation = '';

    public string $location = '';

    public string $managerName = '';

    public string $workEmail = '';

    public string $doj = '';

    public string $phone = '';

    public string $present_address_line1 = '';

    public string $present_address_line2 = '';

    public string $present_city = '';

    public string $present_state = '';

    public string $present_pincode = '';

    public string $present_country = '';

    public string $permanent_address_line1 = '';

    public string $permanent_address_line2 = '';

    public string $permanent_city = '';

    public string $permanent_state = '';

    public string $permanent_pincode = '';

    public string $permanent_country = '';

    public string $emergency_contact_name = '';

    public string $emergency_contact_phone = '';

    public bool $saved = false;

    public function mount(?string $company_id = null): void
    {
        $employee = $this->bootEmployeePortal($company_id);
        $this->hydrateFromEmployee($employee);
    }

    public function save(EmployeeSelfServiceProfileService $profiles): void
    {
        $this->validate([
            'phone' => 'nullable|string|max:30',
            'present_address_line1' => 'nullable|string|max:255',
            'present_address_line2' => 'nullable|string|max:255',
            'present_city' => 'nullable|string|max:100',
            'present_state' => 'nullable|string|max:100',
            'present_pincode' => 'nullable|string|max:20',
            'present_country' => 'nullable|string|max:100',
            'permanent_address_line1' => 'nullable|string|max:255',
            'permanent_address_line2' => 'nullable|string|max:255',
            'permanent_city' => 'nullable|string|max:100',
            'permanent_state' => 'nullable|string|max:100',
            'permanent_pincode' => 'nullable|string|max:20',
            'permanent_country' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:200',
            'emergency_contact_phone' => 'nullable|string|max:30',
        ]);

        $employee = $this->bootEmployeePortal($this->companyId);
        $updated = $profiles->update($employee, [
            'phone' => $this->phone,
            'present_address_line1' => $this->present_address_line1,
            'present_address_line2' => $this->present_address_line2,
            'present_city' => $this->present_city,
            'present_state' => $this->present_state,
            'present_pincode' => $this->present_pincode,
            'present_country' => $this->present_country,
            'permanent_address_line1' => $this->permanent_address_line1,
            'permanent_address_line2' => $this->permanent_address_line2,
            'permanent_city' => $this->permanent_city,
            'permanent_state' => $this->permanent_state,
            'permanent_pincode' => $this->permanent_pincode,
            'permanent_country' => $this->permanent_country,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
        ]);

        $this->hydrateFromEmployee($updated);
        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.ess.my-profile');
    }

    private function hydrateFromEmployee($employee): void
    {
        $this->employeeName = (string) $employee->employee_name;
        $this->employeeCode = (string) $employee->employee_code;
        $this->department = (string) ($employee->department?->department_name ?? '—');
        $this->designation = (string) ($employee->designation?->designation_name ?? '—');
        $this->location = (string) ($employee->location?->name ?? '—');
        $this->managerName = (string) ($employee->manager?->employee_name ?? '—');
        $this->workEmail = (string) ($employee->work_email ?? auth()->user()?->email ?? '');
        $this->doj = $employee->doj?->format('M j, Y') ?? '—';
        $this->phone = (string) ($employee->phone ?? '');
        $this->present_address_line1 = (string) ($employee->present_address_line1 ?? '');
        $this->present_address_line2 = (string) ($employee->present_address_line2 ?? '');
        $this->present_city = (string) ($employee->present_city ?? '');
        $this->present_state = (string) ($employee->present_state ?? '');
        $this->present_pincode = (string) ($employee->present_pincode ?? '');
        $this->present_country = (string) ($employee->present_country ?? '');
        $this->permanent_address_line1 = (string) ($employee->permanent_address_line1 ?? '');
        $this->permanent_address_line2 = (string) ($employee->permanent_address_line2 ?? '');
        $this->permanent_city = (string) ($employee->permanent_city ?? '');
        $this->permanent_state = (string) ($employee->permanent_state ?? '');
        $this->permanent_pincode = (string) ($employee->permanent_pincode ?? '');
        $this->permanent_country = (string) ($employee->permanent_country ?? '');
        $this->emergency_contact_name = (string) ($employee->emergency_contact_name ?? '');
        $this->emergency_contact_phone = (string) ($employee->emergency_contact_phone ?? '');
    }
}
