<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Models\EmployeePayroll;
use App\Services\Ess\EmployeeContext;
use App\Services\Observability\DomainTelemetry;
use Illuminate\Http\Response;

/**
 * Employee-scoped payslip download — reuses admin PDF generation after ownership check.
 */
class EssPayslipController extends Controller
{
    public function download(
        string $company_id,
        int $run_id,
        int $employee_payroll_id,
        DomainTelemetry $telemetry,
        EmployeeContext $employeeContext,
    ): Response {
        $user = auth()->user();
        abort_unless($user, 401);

        $employee = $employeeContext->forUser($user, $company_id);

        $payroll = EmployeePayroll::query()
            ->whereKey($employee_payroll_id)
            ->where('payroll_run_id', $run_id)
            ->firstOrFail();

        abort_unless((int) $payroll->employee_id === (int) $employee->id, 403);

        return app(\App\Http\Controllers\PayslipController::class)
            ->download($company_id, $run_id, $employee_payroll_id, $telemetry);
    }
}
