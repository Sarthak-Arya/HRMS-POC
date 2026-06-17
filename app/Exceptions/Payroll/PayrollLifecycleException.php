<?php

namespace App\Exceptions\Payroll;

use App\Enums\Payroll\EmployeePayrollStatus;
use App\Enums\Payroll\PayrollRunStatus;
use App\Models\PayrollRun;
use RuntimeException;

class PayrollLifecycleException extends RuntimeException
{
    public static function runLocked(PayrollRun $run): self
    {
        return new self("Payroll run {$run->id} is LOCKED and cannot be modified.");
    }

    public static function invalidRunTransition(PayrollRunStatus $from, PayrollRunStatus $to): self
    {
        return new self("Cannot transition payroll run from {$from->value} to {$to->value}.");
    }

    public static function invalidEmployeePayrollTransition(EmployeePayrollStatus $from, EmployeePayrollStatus $to): self
    {
        return new self("Cannot transition employee payroll from {$from->value} to {$to->value}.");
    }

    public static function employeeCompanyMismatch(int $employeeId, int $companyId): self
    {
        return new self("Employee {$employeeId} does not belong to company {$companyId}.");
    }

    public static function attendanceEmployeeMismatch(int $attendanceId, int $employeeId): self
    {
        return new self("Attendance {$attendanceId} does not belong to employee {$employeeId}.");
    }

    public static function attendanceCompanyMismatch(int $attendanceId, int $companyId): self
    {
        return new self("Attendance {$attendanceId} does not belong to company {$companyId}.");
    }

    public static function attendancePeriodMismatch(int $attendanceId, int $month, int $year): self
    {
        return new self("Attendance {$attendanceId} does not match payroll period {$month}/{$year}.");
    }
}
