<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class Dashboard extends Component
{
    public string $companyId = '';
    public string $companyName = '';
    public array $stats = [];
    public $recentEmployees;

    public function mount(?string $company_id = null): void
    {
        $this->companyId = $company_id ?? (string) request()->session()->get('companyId', '');
        if ($this->companyId !== '') {
            request()->session()->put('companyId', $this->companyId);
        }

        $company = Company::find($this->companyId);
        $this->companyName = $company?->company_name ?? 'Dashboard';

        if (!$company) {
            Log::warning('Dashboard loaded with missing company', ['company_id' => $this->companyId]);
            $this->stats = [
                'total_employees' => 0,
                'active_employees' => 0,
                'pf_employees' => 0,
                'esi_employees' => 0,
                'departments' => 0,
                'locations' => 0,
            ];
            $this->recentEmployees = collect();
            return;
        }

        $employeesQuery = Employee::query()->where('company_id', $company->id);
        $this->stats = [
            'total_employees' => (clone $employeesQuery)->count(),
            'active_employees' => (clone $employeesQuery)->whereNull('dol')->count(),
            'pf_employees' => (clone $employeesQuery)->whereNotNull('pf_no')->where('pf_no', '!=', '')->count(),
            'esi_employees' => (clone $employeesQuery)->whereNotNull('esi_no')->where('esi_no', '!=', '')->count(),
            'departments' => Department::where('company_id', $company->id)->count(),
            'locations' => Location::where('company_id', $company->id)->count(),
        ];

        $this->recentEmployees = Employee::with(['department', 'designation', 'location'])
            ->where('company_id', $company->id)
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
