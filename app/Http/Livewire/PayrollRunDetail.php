<?php

namespace App\Http\Livewire;

use App\Enums\Payroll\EmployeePayrollStatus;
use App\Enums\Payroll\PayrollAdjustmentType;
use App\Enums\Payroll\PayrollRunStatus;
use App\Models\AuditLog;
use App\Models\CompensationComponent;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Services\Payroll\PayrollGenerationService;
use App\Services\Payroll\PayrollReadinessService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Livewire\Component;
use Livewire\WithPagination;

class PayrollRunDetail extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public ?string $companyId = null;

    public int $runId;

    public string $activeTab = 'employees';

    public string $selectedDepartment = '';

    public string $selectedDesignation = '';

    public string $search = '';

    public ?string $batchId = null;

    public int $batchProgress = 0;

    public string $batchStatus = '';

    public bool $showAdjustmentModal = false;

    public string $adjustmentEmployeeId = '';

    public string $adjustmentType = 'ADDITION';

    public string $adjustmentAmount = '';

    public string $adjustmentRemarks = '';

    public string $adjustmentComponentId = '';

    public function mount(?string $company_id = null, ?int $run_id = null): void
    {
        $this->companyId = $company_id ?? (string) session('companyId');
        $this->runId = (int) $run_id;
        session()->put('companyId', $this->companyId);
    }

    public function getRunProperty(): PayrollRun
    {
        return PayrollRun::query()
            ->where('company_id', $this->companyId)
            ->with(['company', 'processedBy'])
            ->findOrFail($this->runId);
    }

    public function processPayroll(PayrollGenerationService $generationService): void
    {
        $run = $this->run;

        if ($run->isLocked()) {
            session()->flash('error', 'This payroll run is locked.');

            return;
        }

        try {
            $batch = $generationService->dispatchBatch(
                $run,
                $this->selectedDepartment ? (int) $this->selectedDepartment : null,
                $this->selectedDesignation ? (int) $this->selectedDesignation : null,
            );

            $this->batchId = $batch->id;
            $this->batchStatus = 'processing';
            session()->flash('success', 'Payroll processing started for '.$batch->totalJobs.' employees.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function pollBatchProgress(): void
    {
        if (! $this->batchId) {
            return;
        }

        $batch = Bus::findBatch($this->batchId);

        if (! $batch) {
            return;
        }

        $this->batchProgress = $batch->totalJobs > 0
            ? (int) round((($batch->totalJobs - $batch->pendingJobs) / $batch->totalJobs) * 100)
            : 0;

        $this->batchStatus = $batch->finished() ? 'completed' : 'processing';

        if ($batch->finished()) {
            $this->batchId = null;
        }
    }

    public function approveAll(PayrollGenerationService $generationService): void
    {
        try {
            $count = $generationService->approveAllDraft($this->run);
            session()->flash('success', "Approved {$count} employee payroll(s).");
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function completeRun(PayrollGenerationService $generationService): void
    {
        try {
            $generationService->completeRunIfReady($this->run);
            session()->flash('success', 'Payroll run marked as completed.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function lockRun(PayrollGenerationService $generationService): void
    {
        try {
            $generationService->lockRun($this->run);
            session()->flash('success', 'Payroll run locked. No further edits allowed.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function markAllPaid(PayrollGenerationService $generationService): void
    {
        try {
            $count = $generationService->markAllApprovedAsPaid($this->run);
            session()->flash('success', "Marked {$count} employee payroll(s) as paid.");
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function openAdjustmentModal(?int $employeeId = null): void
    {
        $this->showAdjustmentModal = true;
        $this->adjustmentEmployeeId = $employeeId ? (string) $employeeId : '';
        $this->adjustmentType = PayrollAdjustmentType::ADDITION->value;
        $this->adjustmentAmount = '';
        $this->adjustmentRemarks = '';
        $this->adjustmentComponentId = '';
    }

    public function closeAdjustmentModal(): void
    {
        $this->showAdjustmentModal = false;
    }

    public function saveAdjustment(): void
    {
        $this->validate([
            'adjustmentEmployeeId' => 'required|exists:employees,id',
            'adjustmentType' => 'required|in:ADDITION,DEDUCTION',
            'adjustmentAmount' => 'required|numeric|min:0.01',
            'adjustmentRemarks' => 'nullable|string|max:500',
            'adjustmentComponentId' => 'nullable|exists:compensation_components,id',
        ]);

        $run = $this->run;

        if ($run->isLocked()) {
            session()->flash('error', 'Cannot add adjustments to a locked run.');

            return;
        }

        PayrollAdjustment::create([
            'employee_id' => (int) $this->adjustmentEmployeeId,
            'payroll_run_id' => $run->id,
            'component_id' => $this->adjustmentComponentId ?: null,
            'adjustment_type' => $this->adjustmentType,
            'amount' => $this->adjustmentAmount,
            'remarks' => $this->adjustmentRemarks,
            'created_by' => auth()->id(),
        ]);

        $this->closeAdjustmentModal();
        session()->flash('success', 'Adjustment saved. Reprocess payroll to apply changes.');
    }

    public function viewEmployeePayroll(int $employeePayrollId): void
    {
        $this->redirectRoute('employee-payroll-detail', [
            'company_id' => $this->companyId,
            'run_id' => $this->runId,
            'employee_payroll_id' => $employeePayrollId,
        ]);
    }

    public function render()
    {
        $run = $this->run;
        $companyId = (int) $this->companyId;

        $employeePayrolls = $run->employeePayrolls()
            ->with(['employee.department', 'employee.designation', 'attendanceSummary'])
            ->when($this->search, function ($q) {
                $q->whereHas('employee', function ($eq) {
                    $eq->where('employee_name', 'like', '%'.$this->search.'%')
                        ->orWhere('employee_code', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->selectedDepartment, fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('department_id', $this->selectedDepartment)))
            ->when($this->selectedDesignation, fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('designation_id', $this->selectedDesignation)))
            ->orderBy('id')
            ->paginate(15);

        $readiness = app(PayrollReadinessService::class)->assess(
            $run,
            app(PayrollReadinessService::class)->eligibleEmployees(
                $run,
                $this->selectedDepartment ? (int) $this->selectedDepartment : null,
                $this->selectedDesignation ? (int) $this->selectedDesignation : null,
            ),
        );

        $summary = [
            'total' => $run->employeePayrolls()->count(),
            'draft' => $run->employeePayrolls()->where('status', EmployeePayrollStatus::DRAFT)->count(),
            'approved' => $run->employeePayrolls()->where('status', EmployeePayrollStatus::APPROVED)->count(),
            'paid' => $run->employeePayrolls()->where('status', EmployeePayrollStatus::PAID)->count(),
            'gross' => $run->employeePayrolls()->sum('gross_earnings'),
            'deductions' => $run->employeePayrolls()->sum('gross_deductions'),
            'net' => $run->employeePayrolls()->sum('net_pay'),
        ];

        $adjustments = $run->adjustments()->with(['employee', 'component', 'createdBy'])->latest()->get();
        $history = $run->history()->with('changedBy')->limit(20)->get();

        $employeePayrollIds = $run->employeePayrolls()->pluck('id');
        $auditLogs = AuditLog::query()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($run, $employeePayrollIds) {
                $q->where(function ($inner) use ($run) {
                    $inner->where('auditable_type', PayrollRun::class)
                        ->where('auditable_id', $run->id);
                });

                if ($employeePayrollIds->isNotEmpty()) {
                    $q->orWhere(function ($inner) use ($employeePayrollIds) {
                        $inner->where('auditable_type', EmployeePayroll::class)
                            ->whereIn('auditable_id', $employeePayrollIds);
                    });
                }
            })
            ->latest('changed_at')
            ->limit(30)
            ->get();

        return view('livewire.payroll-run-detail', [
            'run' => $run,
            'employeePayrolls' => $employeePayrolls,
            'readiness' => $readiness,
            'summary' => $summary,
            'adjustments' => $adjustments,
            'history' => $history,
            'auditLogs' => $auditLogs,
            'departments' => Department::where('company_id', $companyId)->get(),
            'designations' => Designation::where('company_id', $companyId)->get(),
            'employees' => Employee::where('company_id', $companyId)->orderBy('employee_name')->get(),
            'components' => CompensationComponent::where('company_id', $companyId)->where('is_active', true)->get(),
            'periodLabel' => Carbon::create($run->year, $run->month)->format('F Y'),
            'statusBadge' => $this->statusBadgeClass($run->status),
        ]);
    }

    private function statusBadgeClass(PayrollRunStatus $status): string
    {
        return match ($status) {
            PayrollRunStatus::DRAFT => 'secondary',
            PayrollRunStatus::PROCESSING => 'info',
            PayrollRunStatus::COMPLETED => 'success',
            PayrollRunStatus::LOCKED => 'dark',
        };
    }
}
