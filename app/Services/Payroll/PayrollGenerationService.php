<?php

namespace App\Services\Payroll;

use App\Enums\Payroll\AuditEventType;
use App\Enums\Payroll\EmployeeLoanStatus;
use App\Enums\Payroll\EmployeePayrollStatus;
use App\Enums\Payroll\PayrollRunStatus;
use App\Jobs\ProcessEmployeePayroll;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanInstallment;
use App\Models\EmployeePayroll;
use App\Models\EmployeePayrollLine;
use App\Models\MonthlyAttendance;
use App\Models\PayrollRun;
use Illuminate\Bus\Batch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Throwable;

class PayrollGenerationService
{
    public function __construct(
        private readonly EmployeePayrollCalculator $calculator,
        private readonly PayrollRunLifecycle $lifecycle,
        private readonly PayrollRunManager $runManager,
        private readonly PayrollReadinessService $readinessService,
        private readonly PayrollAuditLogger $auditLogger,
        private readonly PayrollHistoryRecorder $historyRecorder,
    ) {}

    public function findOrCreateRun(int $companyId, int $month, int $year): PayrollRun
    {
        $run = PayrollRun::query()->firstOrCreate(
            [
                'company_id' => $companyId,
                'month' => $month,
                'year' => $year,
            ],
            [
                'status' => PayrollRunStatus::DRAFT,
            ],
        );

        if ($run->wasRecentlyCreated) {
            $this->auditLogger->log(
                $run,
                AuditEventType::CREATE,
                null,
                $run->toArray(),
                $companyId,
            );
        }

        return $run;
    }

    /**
     * @return array{processed: int, skipped: int, failed: int, skipped_employees: array<int, string>}
     */
    public function processRunSync(
        PayrollRun $run,
        ?int $departmentId = null,
        ?int $designationId = null,
    ): array {
        $this->lifecycle->assertRunEditable($run);

        if ($run->status === PayrollRunStatus::DRAFT) {
            $this->runManager->transitionRunStatus($run, PayrollRunStatus::PROCESSING, 'Payroll processing started');
            $run->refresh();
        }

        $employees = $this->readinessService->eligibleEmployees($run, $departmentId, $designationId);
        $stats = ['processed' => 0, 'skipped' => 0, 'failed' => 0, 'skipped_employees' => []];

        foreach ($employees as $employee) {
            try {
                $result = $this->processEmployee($run, $employee);
                if ($result === null) {
                    $stats['skipped']++;
                    $stats['skipped_employees'][$employee->id] = $employee->employee_name.' (missing prerequisites)';
                } else {
                    $stats['processed']++;
                }
            } catch (Throwable $e) {
                $stats['failed']++;
                $stats['skipped_employees'][$employee->id] = $employee->employee_name.' ('.$e->getMessage().')';
            }
        }

        return $stats;
    }

    public function dispatchBatch(
        PayrollRun $run,
        ?int $departmentId = null,
        ?int $designationId = null,
    ): Batch {
        $this->lifecycle->assertRunEditable($run);

        if (in_array($run->status, [PayrollRunStatus::DRAFT, PayrollRunStatus::COMPLETED], true)) {
            $this->runManager->transitionRunStatus($run, PayrollRunStatus::PROCESSING, 'Payroll batch processing started');
            $run->refresh();
        }

        $employees = $this->readinessService->eligibleEmployees($run, $departmentId, $designationId);

        $jobs = $employees->map(fn (Employee $employee) => new ProcessEmployeePayroll(
            $run->id,
            $employee->id,
        ))->all();

        return Bus::batch($jobs)
            ->name("payroll-run-{$run->id}")
            ->allowFailures()
            ->dispatch();
    }

    public function processEmployee(PayrollRun $run, Employee $employee): ?EmployeePayroll
    {
        $this->lifecycle->assertRunEditable($run);
        $this->lifecycle->assertEmployeeBelongsToRunCompany($employee, $run);

        $attendance = MonthlyAttendance::query()
            ->where('employee_id', $employee->id)
            ->where('company_id', $run->company_id)
            ->where('month', $run->month)
            ->where('year', $run->year)
            ->first();

        if ($attendance === null) {
            return null;
        }

        $this->lifecycle->assertAttendanceBelongsToEmployee($attendance, $employee, $run);

        $existing = EmployeePayroll::query()
            ->where('payroll_run_id', $run->id)
            ->where('employee_id', $employee->id)
            ->first();

        if ($existing !== null && $existing->status !== EmployeePayrollStatus::DRAFT) {
            return $existing;
        }

        $calculated = $this->calculator->calculate($employee, $run, $attendance);

        return DB::transaction(function () use ($run, $employee, $attendance, $calculated, $existing) {
            if ($existing !== null) {
                $this->historyRecorder->snapshotEmployeePayroll($existing, Auth::id(), 'Recalculation');
                $existing->lines()->delete();
                $this->removeLoanInstallmentsForRun($run, $employee);
            }

            $payroll = $existing ?? new EmployeePayroll([
                'payroll_run_id' => $run->id,
                'employee_id' => $employee->id,
                'status' => EmployeePayrollStatus::DRAFT,
            ]);

            $payroll->fill([
                'attendance_summary_id' => $attendance->id,
                'employee_compensation_id' => $calculated->compensationHistory->id,
                'gross_earnings' => $calculated->grossEarnings,
                'gross_deductions' => $calculated->grossDeductions,
                'employer_contributions' => $calculated->employerContributions,
                'net_pay' => $calculated->netPay,
                'status' => EmployeePayrollStatus::DRAFT,
            ]);
            $payroll->save();

            foreach ($calculated->lines as $lineData) {
                $line = EmployeePayrollLine::create([
                    'employee_payroll_id' => $payroll->id,
                    'component_id' => $lineData['component_id'],
                    'component_name' => $lineData['component_name'],
                    'component_type' => $lineData['component_type'],
                    'calculated_amount' => $lineData['calculated_amount'],
                    'calculation_basis' => $lineData['calculation_basis'],
                ]);

                $this->auditLogger->log(
                    $line,
                    AuditEventType::CALCULATION,
                    null,
                    $line->toArray(),
                    $run->company_id,
                );
            }

            foreach ($calculated->loanInstallments as $installmentData) {
                /** @var EmployeeLoan $loan */
                $loan = $installmentData['loan'];

                EmployeeLoanInstallment::create([
                    'loan_id' => $loan->id,
                    'payroll_run_id' => $run->id,
                    'installment_no' => $installmentData['installment_no'],
                    'amount' => $installmentData['amount'],
                    'deducted_on' => now()->toDateString(),
                ]);

                $loan->remaining_amount = max(0, round((float) $loan->remaining_amount - (float) $installmentData['amount'], 2));
                if ((float) $loan->remaining_amount <= 0) {
                    $loan->status = EmployeeLoanStatus::CLOSED;
                }
                $loan->save();
            }

            $this->auditLogger->log(
                $payroll,
                $existing ? AuditEventType::UPDATE : AuditEventType::CREATE,
                $existing?->toArray(),
                $payroll->fresh(['lines'])->toArray(),
                $run->company_id,
            );

            return $payroll->fresh(['lines', 'employee', 'attendanceSummary']);
        });
    }

    public function approveAllDraft(PayrollRun $run): int
    {
        $count = 0;
        $payrolls = $run->employeePayrolls()->where('status', EmployeePayrollStatus::DRAFT)->get();

        foreach ($payrolls as $payroll) {
            $this->runManager->transitionEmployeePayrollStatus($payroll, EmployeePayrollStatus::APPROVED, 'Bulk approval');
            $count++;
        }

        return $count;
    }

    public function markAllApprovedAsPaid(PayrollRun $run): int
    {
        $count = 0;
        $payrolls = $run->employeePayrolls()->where('status', EmployeePayrollStatus::APPROVED)->get();

        foreach ($payrolls as $payroll) {
            $this->runManager->transitionEmployeePayrollStatus($payroll, EmployeePayrollStatus::PAID, 'Bulk payment');
            $count++;
        }

        return $count;
    }

    public function completeRunIfReady(PayrollRun $run): PayrollRun
    {
        $run->load('employeePayrolls');

        if ($run->employeePayrolls->isEmpty()) {
            throw new \RuntimeException('Cannot complete payroll run with no employee records.');
        }

        $pending = $run->employeePayrolls->filter(
            fn ($payroll) => ! in_array($payroll->status, [EmployeePayrollStatus::APPROVED, EmployeePayrollStatus::PAID], true),
        );

        if ($pending->isNotEmpty()) {
            throw new \RuntimeException('All employee payrolls must be approved before completing the run.');
        }

        if ($run->status === PayrollRunStatus::DRAFT) {
            $this->runManager->transitionRunStatus($run, PayrollRunStatus::PROCESSING, 'Preparing for completion');
            $run->refresh();
        }

        return $this->runManager->transitionRunStatus($run, PayrollRunStatus::COMPLETED, 'Payroll run completed');
    }

    public function lockRun(PayrollRun $run): PayrollRun
    {
        if ($run->status !== PayrollRunStatus::COMPLETED) {
            throw new \RuntimeException('Only completed payroll runs can be locked.');
        }

        return $this->runManager->transitionRunStatus($run, PayrollRunStatus::LOCKED, 'Payroll run locked');
    }

    private function removeLoanInstallmentsForRun(PayrollRun $run, Employee $employee): void
    {
        $installments = EmployeeLoanInstallment::query()
            ->where('payroll_run_id', $run->id)
            ->whereHas('loan', fn ($q) => $q->where('employee_id', $employee->id))
            ->get();

        foreach ($installments as $installment) {
            $loan = $installment->loan;
            $loan->remaining_amount = round((float) $loan->remaining_amount + (float) $installment->amount, 2);
            if ($loan->status === EmployeeLoanStatus::CLOSED) {
                $loan->status = EmployeeLoanStatus::ACTIVE;
            }
            $loan->save();
            $installment->delete();
        }
    }
}
