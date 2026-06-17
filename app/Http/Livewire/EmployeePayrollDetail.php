<?php

namespace App\Http\Livewire;

use App\Enums\Payroll\EmployeePayrollStatus;
use App\Enums\Payroll\PayrollLineComponentType;
use App\Models\EmployeePayroll;
use App\Services\Payroll\PayrollGenerationService;
use App\Services\Payroll\PayrollRunManager;
use Carbon\Carbon;
use Livewire\Component;

class EmployeePayrollDetail extends Component
{
    public ?string $companyId = null;

    public int $runId;

    public int $employeePayrollId;

    public function mount(?string $company_id = null, ?int $run_id = null, ?int $employee_payroll_id = null): void
    {
        $this->companyId = $company_id ?? (string) session('companyId');
        $this->runId = (int) $run_id;
        $this->employeePayrollId = (int) $employee_payroll_id;
        session()->put('companyId', $this->companyId);
    }

    public function getPayrollProperty(): EmployeePayroll
    {
        return EmployeePayroll::query()
            ->where('payroll_run_id', $this->runId)
            ->with([
                'employee.department',
                'employee.designation',
                'employee.company',
                'attendanceSummary',
                'employeeCompensation.structure',
                'lines',
                'payrollRun',
            ])
            ->findOrFail($this->employeePayrollId);
    }

    public function approve(PayrollRunManager $runManager): void
    {
        try {
            $runManager->transitionEmployeePayrollStatus($this->payroll, EmployeePayrollStatus::APPROVED, 'Manual approval');
            session()->flash('success', 'Employee payroll approved.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function revertToDraft(PayrollRunManager $runManager): void
    {
        try {
            $runManager->transitionEmployeePayrollStatus($this->payroll, EmployeePayrollStatus::DRAFT, 'Reverted to draft');
            session()->flash('success', 'Employee payroll reverted to draft.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function markPaid(PayrollRunManager $runManager): void
    {
        try {
            $runManager->transitionEmployeePayrollStatus($this->payroll, EmployeePayrollStatus::PAID, 'Marked as paid');
            session()->flash('success', 'Employee payroll marked as paid.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function recalculate(PayrollGenerationService $generationService): void
    {
        try {
            $payroll = $this->payroll;

            if ($payroll->status !== EmployeePayrollStatus::DRAFT) {
                session()->flash('error', 'Only draft payrolls can be recalculated.');

                return;
            }

            $generationService->processEmployee($payroll->payrollRun, $payroll->employee);
            session()->flash('success', 'Payroll recalculated successfully.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function backToRun(): void
    {
        $this->redirectRoute('payroll-run-detail', [
            'company_id' => $this->companyId,
            'run_id' => $this->runId,
        ]);
    }

    public function render()
    {
        $payroll = $this->payroll;
        $run = $payroll->payrollRun;

        return view('livewire.employee-payroll-detail', [
            'payroll' => $payroll,
            'run' => $run,
            'earnings' => $payroll->lines->where('component_type', PayrollLineComponentType::EARNING),
            'deductions' => $payroll->lines->where('component_type', PayrollLineComponentType::DEDUCTION),
            'employer' => $payroll->lines->where('component_type', PayrollLineComponentType::EMPLOYER_CONTRIBUTION),
            'periodLabel' => Carbon::create($run->year, $run->month)->format('F Y'),
        ]);
    }
}
