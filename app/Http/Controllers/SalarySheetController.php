<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Services\Observability\DomainTelemetry;
use App\Services\Payroll\SalarySheetExporter;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SalarySheetController extends Controller
{
    public function download(
        string $company_id,
        int $run_id,
        SalarySheetExporter $exporter,
        DomainTelemetry $telemetry,
    ): Response|BinaryFileResponse {
        $groupBy = request()->query('group_by', 'company');
        $format = request()->query('format', 'pdf');

        $run = PayrollRun::query()
            ->where('company_id', (int) $company_id)
            ->with('company')
            ->findOrFail($run_id);

        try {
            $response = $exporter->download($run, $groupBy, $format);
            $telemetry->emit('export.salary_sheet.downloaded', 'audit', 'success', [
                'company.id' => (int) $company_id,
                'payroll.run_id' => $run_id,
                'artifact_type' => 'salary_sheet',
                'format' => is_string($format) ? $format : 'pdf',
            ]);

            return $response;
        } catch (ValidationException $e) {
            $telemetry->emit('export.salary_sheet.downloaded', 'audit', 'failure', [
                'company.id' => (int) $company_id,
                'payroll.run_id' => $run_id,
                'artifact_type' => 'salary_sheet',
                'error.type' => ValidationException::class,
            ], 'warning');
            throw $e;
        } catch (Throwable $e) {
            $telemetry->emit('export.salary_sheet.downloaded', 'audit', 'failure', [
                'company.id' => (int) $company_id,
                'payroll.run_id' => $run_id,
                'artifact_type' => 'salary_sheet',
                'error.type' => $e::class,
            ], 'error');
            throw $e;
        }
    }
}
