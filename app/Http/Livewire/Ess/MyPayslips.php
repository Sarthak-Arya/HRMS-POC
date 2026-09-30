<?php

namespace App\Http\Livewire\Ess;

use App\Http\Livewire\Ess\Concerns\ResolvesEmployeePortal;
use App\Models\EmployeePayroll;
use Livewire\Component;

class MyPayslips extends Component
{
    use ResolvesEmployeePortal;

    public function mount(?string $company_id = null): void
    {
        $this->bootEmployeePortal($company_id);
    }

    public function render()
    {
        $employee = $this->bootEmployeePortal($this->companyId);

        $payslips = EmployeePayroll::query()
            ->with('payrollRun')
            ->where('employee_id', $employee->id)
            ->whereHas('payrollRun', fn ($q) => $q->where('company_id', $this->companyId))
            ->latest('id')
            ->limit(36)
            ->get();

        return view('livewire.ess.my-payslips', [
            'payslips' => $payslips,
        ]);
    }
}
