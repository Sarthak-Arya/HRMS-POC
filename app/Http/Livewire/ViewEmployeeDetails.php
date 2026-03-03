<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Employee;

class ViewEmployeeDetails extends Component
{
    public string $companyId = '';
    public string $employeeId = '';

    public function mount(?string $company_id = null, ?string $employee_id = null): void
    {
        $this->companyId = $company_id ?? (string) session()->get('companyId', '');
        $this->employeeId = $employee_id ?? '';

        if ($this->companyId !== '') {
            session()->put('companyId', $this->companyId);
        }
    }

    public function render()
    {
        $employee = null;
        if ($this->companyId !== '' && $this->employeeId !== '') {
            $employee = Employee::with(['department', 'designation', 'location'])
                ->where('company_id', $this->companyId)
                ->find($this->employeeId);
        }

        return view('livewire.view-employee-details', [
            'employee' => $employee,
            'companyId' => $this->companyId,
        ]);
    }
}
