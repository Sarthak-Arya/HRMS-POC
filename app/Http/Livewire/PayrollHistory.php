<?php

namespace App\Http\Livewire;

use App\Enums\Payroll\PayrollRunStatus;
use App\Models\PayrollRun;
use Livewire\Component;
use Livewire\WithPagination;

class PayrollHistory extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public ?string $companyId = null;

    public int $filterYear;

    public function mount(?string $company_id = null): void
    {
        $this->companyId = $company_id ?? (string) session('companyId');
        session()->put('companyId', $this->companyId);
        $this->filterYear = (int) now()->year;
    }

    public function updatedFilterYear(): void
    {
        $this->resetPage();
    }

    public function openRun(int $runId): void
    {
        $this->redirectRoute('payroll-run-detail', [
            'company_id' => $this->companyId,
            'run_id' => $runId,
        ]);
    }

    public function render()
    {
        $runs = PayrollRun::query()
            ->where('company_id', $this->companyId)
            ->where('year', $this->filterYear)
            ->whereIn('status', [PayrollRunStatus::COMPLETED, PayrollRunStatus::LOCKED])
            ->withCount('employeePayrolls')
            ->with('processedBy')
            ->orderByDesc('month')
            ->paginate(12);

        return view('livewire.payroll-history', [
            'runs' => $runs,
        ]);
    }
}
