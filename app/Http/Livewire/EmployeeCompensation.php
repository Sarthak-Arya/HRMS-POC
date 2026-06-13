<?php

namespace App\Http\Livewire;

use App\Models\Employee;
use Livewire\Component;

/**
 * Livewire component for managing employee compensation view.
 * Serves as a container for compensation details of a specific employee.
 */
class EmployeeCompensation extends Component
{
    /** @var string The ID of the company */
    public string $companyId = '';

    /** @var string The ID of the employee */
    public string $employeeId = '';

    /**
     * Initialize the component with company and employee IDs.
     *
     * @param string|null $company_id The ID of the company.
     * @param string|null $employee_id The ID of the employee.
     * @return void
     */
    public function mount(?string $company_id = null, ?string $employee_id = null): void
    {
        $this->companyId = $company_id ?? (string) session()->get('companyId', '');
        $this->employeeId = $employee_id ?? '';

        if ($this->companyId !== '') {
            session()->put('companyId', $this->companyId);
        }
    }

    /**
     * Render the component view.
     *
     * @return \Illuminate\View\View The rendered view.
     */
    public function render()
    {
        $employee = null;
        if ($this->companyId !== '' && $this->employeeId !== '') {
            $employee = Employee::where('company_id', $this->companyId)->find($this->employeeId);
        }

        return view('livewire.employee-compensation', [
            'employee' => $employee,
        ]);
    }
}
