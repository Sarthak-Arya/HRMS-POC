<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeeCompensationHistory;
use App\Models\MonthlyAttendance;

class CalculatedPayrollResult
{
    /**
     * @param array<int, array<string, mixed>> $lines
     * @param array<int, array<string, mixed>> $loanInstallments
     */
    public function __construct(
        public Employee $employee,
        public MonthlyAttendance $attendance,
        public EmployeeCompensationHistory $compensationHistory,
        public array $lines,
        public float $grossEarnings,
        public float $grossDeductions,
        public float $employerContributions,
        public float $netPay,
        public array $loanInstallments = [],
        public float $prorationFactor = 1.0,
    ) {}
}
