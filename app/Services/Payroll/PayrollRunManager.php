<?php

namespace App\Services\Payroll;

use App\Enums\Payroll\AuditEventType;
use App\Enums\Payroll\EmployeePayrollStatus;
use App\Enums\Payroll\PayrollRunStatus;
use App\Models\EmployeePayroll;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollRunManager
{
    public function __construct(
        private readonly PayrollRunLifecycle $lifecycle,
        private readonly PayrollAuditLogger $auditLogger,
        private readonly PayrollHistoryRecorder $historyRecorder,
    ) {}

    public function transitionRunStatus(PayrollRun $run, PayrollRunStatus $target, ?string $reason = null): PayrollRun
    {
        return DB::transaction(function () use ($run, $target, $reason) {
            $run->refresh();
            $oldStatus = $run->status;

            if ($oldStatus === $target) {
                return $run;
            }

            $this->historyRecorder->snapshotPayrollRun($run, Auth::id(), $reason);
            $this->lifecycle->transitionRunStatus($run, $target);

            if (in_array($target, [PayrollRunStatus::COMPLETED, PayrollRunStatus::LOCKED], true)) {
                $run->processed_by = $run->processed_by ?? Auth::id();
                $run->processed_at = $run->processed_at ?? now();
            }

            $run->save();

            $this->auditLogger->log(
                $run,
                AuditEventType::STATUS_CHANGE,
                ['status' => $oldStatus->value],
                ['status' => $target->value],
                $run->company_id,
                'payroll_run_manager',
            );

            return $run->fresh();
        });
    }

    public function transitionEmployeePayrollStatus(
        EmployeePayroll $payroll,
        EmployeePayrollStatus $target,
        ?string $reason = null,
    ): EmployeePayroll {
        return DB::transaction(function () use ($payroll, $target, $reason) {
            $payroll->load('payrollRun');
            $payroll->refresh();
            $oldStatus = $payroll->status;

            if ($oldStatus === $target) {
                return $payroll;
            }

            $this->historyRecorder->snapshotEmployeePayroll($payroll, Auth::id(), $reason);
            $this->lifecycle->transitionEmployeePayrollStatus($payroll, $target);
            $payroll->save();

            $this->auditLogger->log(
                $payroll,
                AuditEventType::STATUS_CHANGE,
                ['status' => $oldStatus->value],
                ['status' => $target->value],
                $payroll->payrollRun->company_id,
                'payroll_run_manager',
            );

            return $payroll->fresh();
        });
    }
}
