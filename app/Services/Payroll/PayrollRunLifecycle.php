<?php

namespace App\Services\Payroll;

use App\Enums\Payroll\EmployeePayrollStatus;
use App\Enums\Payroll\PayrollRunStatus;
use App\Exceptions\Payroll\PayrollLifecycleException;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use App\Models\MonthlyAttendance;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Model;

class PayrollRunLifecycle
{
    public function assertRunEditable(PayrollRun $run): void
    {
        if ($run->isLocked()) {
            throw PayrollLifecycleException::runLocked($run);
        }
    }

    public function assertChildWritable(Model $model): void
    {
        $run = $this->resolvePayrollRun($model);

        if ($run !== null) {
            $this->assertRunEditable($run);
        }
    }

    public function transitionRunStatus(PayrollRun $run, PayrollRunStatus $target): void
    {
        $current = $run->status;

        if ($current === $target) {
            return;
        }

        if (! $current->canTransitionTo($target)) {
            throw PayrollLifecycleException::invalidRunTransition($current, $target);
        }

        $run->status = $target;

        if ($target === PayrollRunStatus::COMPLETED) {
            $run->processed_at = now();
        }
    }

    public function transitionEmployeePayrollStatus(EmployeePayroll $payroll, EmployeePayrollStatus $target): void
    {
        $this->assertRunEditable($payroll->payrollRun);

        $current = $payroll->status;

        if ($current === $target) {
            return;
        }

        if (! $current->canTransitionTo($target)) {
            throw PayrollLifecycleException::invalidEmployeePayrollTransition($current, $target);
        }

        $payroll->status = $target;
    }

    public function assertEmployeeBelongsToRunCompany(Employee $employee, PayrollRun $run): void
    {
        if ((int) $employee->company_id !== (int) $run->company_id) {
            throw PayrollLifecycleException::employeeCompanyMismatch($employee->id, $run->company_id);
        }
    }

    public function assertAttendanceBelongsToEmployee(MonthlyAttendance $attendance, Employee $employee, PayrollRun $run): void
    {
        if ((int) $attendance->employee_id !== (int) $employee->id) {
            throw PayrollLifecycleException::attendanceEmployeeMismatch($attendance->id, $employee->id);
        }

        if ((int) $attendance->company_id !== (int) $run->company_id) {
            throw PayrollLifecycleException::attendanceCompanyMismatch($attendance->id, $run->company_id);
        }

        if ((int) $attendance->month !== (int) $run->month || (int) $attendance->year !== (int) $run->year) {
            throw PayrollLifecycleException::attendancePeriodMismatch($attendance->id, $run->month, $run->year);
        }
    }

    private function resolvePayrollRun(Model $model): ?PayrollRun
    {
        if ($model instanceof PayrollRun) {
            return $model;
        }

        if ($model instanceof EmployeePayroll) {
            return $model->relationLoaded('payrollRun')
                ? $model->payrollRun
                : $model->payrollRun()->first();
        }

        if (method_exists($model, 'employeePayroll')) {
            $payroll = $model->relationLoaded('employeePayroll')
                ? $model->employeePayroll
                : $model->employeePayroll()->with('payrollRun')->first();

            return $payroll?->payrollRun;
        }

        if (method_exists($model, 'payrollRun')) {
            return $model->relationLoaded('payrollRun')
                ? $model->payrollRun
                : $model->payrollRun()->first();
        }

        return null;
    }
}
