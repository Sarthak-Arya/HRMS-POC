<?php

namespace Database\Seeders;

use App\Enums\Payroll\EmployeeLoanStatus;
use App\Enums\Payroll\EmployeePayrollStatus;
use App\Enums\Payroll\PayrollAdjustmentType;
use App\Enums\Payroll\PayrollLineComponentType;
use App\Enums\Payroll\PayrollRunStatus;
use App\Models\Company;
use App\Models\CompensationComponent;
use App\Models\Employee;
use App\Models\EmployeeCompensationHistory;
use App\Models\EmployeeLoan;
use App\Models\EmployeePayroll;
use App\Models\EmployeePayrollLine;
use App\Models\MonthlyAttendance;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Database\Seeder;

class PayrollV2Seeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->first();

        if ($company === null) {
            $this->command?->warn('PayrollV2Seeder skipped: no company found.');

            return;
        }

        $employee = Employee::query()->where('company_id', $company->id)->first();
        $compensation = $employee
            ? EmployeeCompensationHistory::query()->where('employee_id', $employee->id)->latest('effective_from')->first()
            : null;

        if ($employee === null || $compensation === null) {
            $this->command?->warn('PayrollV2Seeder skipped: employee or compensation history missing.');

            return;
        }

        $month = 6;
        $year = 2026;

        $attendance = MonthlyAttendance::query()->firstOrCreate(
            [
                'employee_id' => $employee->id,
                'company_id' => $company->id,
                'month' => $month,
                'year' => $year,
            ],
            [
                'total_days' => 30,
                'worked_days' => 28,
                'casual_leave' => 1,
                'earned_leave' => 0,
                'sick_leave' => 1,
                'holiday' => 0,
            ],
        );

        $processedBy = User::query()->first();

        $run = PayrollRun::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'month' => $month,
                'year' => $year,
            ],
            [
                'status' => PayrollRunStatus::COMPLETED,
                'processed_by' => $processedBy?->id,
                'processed_at' => now(),
            ],
        );

        $payroll = EmployeePayroll::query()->firstOrCreate(
            [
                'payroll_run_id' => $run->id,
                'employee_id' => $employee->id,
            ],
            [
                'attendance_summary_id' => $attendance->id,
                'employee_compensation_id' => $compensation->id,
                'gross_earnings' => 55000,
                'gross_deductions' => 3800,
                'employer_contributions' => 1800,
                'net_pay' => 51200,
                'status' => EmployeePayrollStatus::APPROVED,
            ],
        );

        $basicComponent = CompensationComponent::query()
            ->where('company_id', $company->id)
            ->where('component_name', 'Basic')
            ->first();

        EmployeePayrollLine::query()->firstOrCreate(
            [
                'employee_payroll_id' => $payroll->id,
                'component_name' => 'Basic',
                'component_type' => PayrollLineComponentType::EARNING,
            ],
            [
                'component_id' => $basicComponent?->id,
                'calculated_amount' => 30000,
                'calculation_basis' => [
                    'type' => 'FIXED',
                    'worked_days' => $attendance->worked_days,
                    'total_days' => $attendance->total_days,
                ],
            ],
        );

        EmployeePayrollLine::query()->firstOrCreate(
            [
                'employee_payroll_id' => $payroll->id,
                'component_name' => 'PF',
                'component_type' => PayrollLineComponentType::DEDUCTION,
            ],
            [
                'calculated_amount' => 3600,
                'calculation_basis' => ['type' => 'PERCENT_BASIC', 'rate' => 12],
            ],
        );

        if ($processedBy !== null) {
            PayrollAdjustment::query()->firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'payroll_run_id' => $run->id,
                    'adjustment_type' => PayrollAdjustmentType::ADDITION,
                    'amount' => 5000,
                ],
                [
                    'remarks' => 'Performance bonus',
                    'created_by' => $processedBy->id,
                ],
            );
        }

        EmployeeLoan::query()->firstOrCreate(
            [
                'employee_id' => $employee->id,
                'loan_name' => 'Salary Advance',
            ],
            [
                'principal_amount' => 50000,
                'emi_amount' => 5000,
                'start_month' => $month,
                'start_year' => $year,
                'remaining_amount' => 45000,
                'status' => EmployeeLoanStatus::ACTIVE,
            ],
        );
    }
}
