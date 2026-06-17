<?php

namespace App\Http\Livewire;

use App\Enums\Payroll\PayrollRunStatus;
use App\Models\PayrollRun;
use App\Services\Payroll\PayrollGenerationService;
use App\Services\Payroll\PayrollReadinessService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class PayrollRunList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public ?string $companyId = null;

    public int $filterMonth;

    public int $filterYear;

    public int $createMonth;

    public int $createYear;

    public function mount(?string $company_id = null): void
    {
        $this->companyId = $company_id ?? (string) session('companyId');
        session()->put('companyId', $this->companyId);

        $now = now();
        $this->filterMonth = (int) $now->month;
        $this->filterYear = (int) $now->year;
        $this->createMonth = (int) $now->month;
        $this->createYear = (int) $now->year;
    }

    public function updatedFilterMonth(): void
    {
        $this->resetPage();
    }

    public function updatedFilterYear(): void
    {
        $this->resetPage();
    }

    public function createRun(PayrollGenerationService $generationService): void
    {
        $run = $generationService->findOrCreateRun(
            (int) $this->companyId,
            $this->createMonth,
            $this->createYear,
        );

        session()->flash('success', 'Payroll run opened for '.Carbon::create($run->year, $run->month)->format('F Y').'.');

        $this->redirectRoute('payroll-run-detail', [
            'company_id' => $this->companyId,
            'run_id' => $run->id,
        ]);
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
        $companyId = (int) $this->companyId;

        $runs = PayrollRun::query()
            ->where('company_id', $companyId)
            ->when($this->filterMonth, fn ($q) => $q->where('month', $this->filterMonth))
            ->when($this->filterYear, fn ($q) => $q->where('year', $this->filterYear))
            ->withCount('employeePayrolls')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(10);

        $statusCounts = PayrollRun::query()
            ->where('company_id', $companyId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $readiness = null;
        $existingDraft = PayrollRun::query()
            ->where('company_id', $companyId)
            ->where('month', $this->createMonth)
            ->where('year', $this->createYear)
            ->where('status', PayrollRunStatus::DRAFT)
            ->first();

        if ($existingDraft) {
            $readiness = app(PayrollReadinessService::class)->assess($existingDraft);
        }

        return view('livewire.payroll-run-list', [
            'runs' => $runs,
            'statusCounts' => $statusCounts,
            'readiness' => $readiness,
            'monthOptions' => collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => Carbon::create(null, $m)->format('F')]),
        ]);
    }
}
