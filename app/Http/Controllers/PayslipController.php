<?php

namespace App\Http\Controllers;

use App\Enums\Payroll\PayrollLineComponentType;
use App\Models\EmployeePayroll;
use App\Models\PayrollRun;
use App\Services\Observability\DomainTelemetry;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Throwable;

class PayslipController extends Controller
{
    public function download(string $company_id, int $run_id, int $employee_payroll_id, DomainTelemetry $telemetry): Response
    {
        try {
            $data = $this->buildPayslipData((int) $company_id, $run_id, $employee_payroll_id);
            $response = $this->pdfResponse(View::make('payroll.payslip-pdf', $data)->render(), $data['filename']);
            $telemetry->emit('export.payslip.downloaded', 'audit', 'success', [
                'company.id' => (int) $company_id,
                'payroll.run_id' => $run_id,
                'artifact_type' => 'payslip',
                'format' => 'pdf',
                'row_count' => 1,
            ]);

            return $response;
        } catch (Throwable $e) {
            $telemetry->emit('export.payslip.downloaded', 'audit', 'failure', [
                'company.id' => (int) $company_id,
                'payroll.run_id' => $run_id,
                'artifact_type' => 'payslip',
                'error.type' => $e::class,
            ], 'error');
            throw $e;
        }
    }

    public function downloadBulk(string $company_id, int $run_id, DomainTelemetry $telemetry): Response
    {
        try {
            $run = PayrollRun::query()->where('company_id', $company_id)->findOrFail($run_id);
            $pages = $run->employeePayrolls()->with(['employee', 'lines'])->get()
                ->map(fn ($payroll) => $this->buildPayslipData((int) $company_id, $run_id, $payroll->id))
                ->all();

            $filename = 'payslips-'.Carbon::create($run->year, $run->month)->format('Y-m').'.pdf';

            $response = $this->pdfResponse(View::make('payroll.payslip-bulk-pdf', ['pages' => $pages])->render(), $filename);
            $telemetry->emit('export.payslip_bulk.downloaded', 'audit', 'success', [
                'company.id' => (int) $company_id,
                'payroll.run_id' => $run_id,
                'artifact_type' => 'payslip_bulk',
                'format' => 'pdf',
                'row_count' => count($pages),
            ]);

            return $response;
        } catch (Throwable $e) {
            $telemetry->emit('export.payslip_bulk.downloaded', 'audit', 'failure', [
                'company.id' => (int) $company_id,
                'payroll.run_id' => $run_id,
                'artifact_type' => 'payslip_bulk',
                'error.type' => $e::class,
            ], 'error');
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayslipData(int $companyId, int $runId, int $employeePayrollId): array
    {
        $payroll = EmployeePayroll::query()
            ->where('payroll_run_id', $runId)
            ->with(['employee.department', 'employee.designation', 'employee.company', 'attendanceSummary', 'lines', 'payrollRun'])
            ->findOrFail($employeePayrollId);

        abort_unless((int) $payroll->payrollRun->company_id === $companyId, 403);

        $run = $payroll->payrollRun;

        return [
            'payroll' => $payroll,
            'run' => $run,
            'employee' => $payroll->employee,
            'company' => $payroll->employee->company,
            'period' => Carbon::create($run->year, $run->month)->format('F Y'),
            'earnings' => $payroll->lines->where('component_type', PayrollLineComponentType::EARNING),
            'deductions' => $payroll->lines->whereIn('component_type', [PayrollLineComponentType::DEDUCTION, PayrollLineComponentType::BENEFIT]),
            'employer' => $payroll->lines->where('component_type', PayrollLineComponentType::EMPLOYER_CONTRIBUTION),
            'filename' => 'payslip-'.$payroll->employee->employee_code.'-'.Carbon::create($run->year, $run->month)->format('Y-m').'.pdf',
        ];
    }

    private function pdfResponse(string $html, string $filename): Response
    {
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4')->download($filename);
        }

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="'.str_replace('.pdf', '.html', $filename).'"',
        ]);
    }
}
