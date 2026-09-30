<?php

namespace App\Http\Livewire\Ess;

use App\Http\Livewire\Ess\Concerns\ResolvesEmployeePortal;
use App\Models\CompanyHoliday;
use App\Models\Employee;
use Livewire\Component;

class EssDirectory extends Component
{
    use ResolvesEmployeePortal;

    public string $search = '';

    public function mount(?string $company_id = null): void
    {
        $this->bootEmployeePortal($company_id);
    }

    public function render()
    {
        $this->bootEmployeePortal($this->companyId);

        $employees = Employee::query()
            ->with(['department', 'designation'])
            ->where('company_id', $this->companyId)
            ->whereNull('dol')
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('employee_name', 'like', $term)
                        ->orWhere('employee_code', 'like', $term)
                        ->orWhere('work_email', 'like', $term);
                });
            })
            ->orderBy('employee_name')
            ->limit(100)
            ->get();

        $holidays = CompanyHoliday::query()
            ->where('company_id', $this->companyId)
            ->where('is_active', true)
            ->whereYear('holiday_date', now()->year)
            ->orderBy('holiday_date')
            ->get();

        return view('livewire.ess.directory', [
            'employees' => $employees,
            'holidays' => $holidays,
        ]);
    }
}
