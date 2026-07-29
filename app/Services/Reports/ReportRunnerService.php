<?php

namespace App\Services\Reports;

use App\Enums\Payroll\AuditEventType;
use App\Enums\Reports\ReportColumnFormat;
use App\Enums\Reports\ReportDataSourceKey;
use App\Enums\Reports\ReportRunStatus;
use App\Exports\GenericReportExport;
use App\Models\CompanyReportTemplate;
use App\Models\PayrollRun;
use App\Models\ReportRun;
use App\Services\Payroll\SalarySheetExporter;
use App\Services\Payroll\SalarySheetService;
use App\Services\Observability\DomainTelemetry;
use App\Services\Reports\DataSources\ReportDataSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportRunnerService
{
    public function __construct(
        private readonly ReportDataSourceRegistry $registry,
        private readonly ReportTemplateConfigValidator $configValidator,
        private readonly ReportTemplateConfigNormalizer $configNormalizer,
        private readonly ReportAuditLogger $auditLogger,
        private readonly SalarySheetService $salarySheetService,
        private readonly SalarySheetExporter $salarySheetExporter,
        private readonly DomainTelemetry $telemetry,
    ) {}

    /**
     * @param  array<string, mixed>  $parameters
     * @return array{headings: list<string>, rows: list<list<mixed>>, total: int}
     */
    public function preview(int $companyId, CompanyReportTemplate $template, array $parameters, int $limit = 25): array
    {
        $this->telemetry->emit('report.preview.requested', 'business', 'success', [
            'company.id' => $companyId,
            'artifact_type' => $template->data_source,
        ]);

        if ($template->data_source === ReportDataSourceKey::SalarySheet->value) {
            return $this->previewSalarySheet($companyId, $template, $parameters, $limit);
        }

        $normalized = $this->validatedConfig($template);
        $source = $this->resolveSource($template);
        $this->validateParameters($source, $parameters, $normalized);

        $columnKeys = $this->configNormalizer->visibleColumnKeys($normalized);
        $query = $this->buildQuery($companyId, $source, $normalized, $parameters);
        $total = (clone $query)->count();
        $records = $query->limit($limit)->get();

        return [
            'headings' => $this->configNormalizer->headingsForColumns($normalized, $source->columns()),
            'rows' => $this->mapRows($source, $records, $normalized),
            'total' => $total,
        ];
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function export(int $companyId, CompanyReportTemplate $template, array $parameters, string $format = 'xlsx'): BinaryFileResponse|\Illuminate\Http\Response
    {
        $started = microtime(true);
        $this->telemetry->emit('report.export.started', 'business', 'success', [
            'company.id' => $companyId,
            'artifact_type' => $template->data_source,
            'format' => $format,
        ]);

        try {
            if ($template->data_source === ReportDataSourceKey::SalarySheet->value) {
                $response = $this->exportSalarySheet($companyId, $template, $parameters, $format);
            } else {
                $response = $this->exportGeneric($companyId, $template, $parameters, $format);
            }

            $this->telemetry->emit('report.export.completed', 'business', 'success', [
                'company.id' => $companyId,
                'artifact_type' => $template->data_source,
                'format' => $format,
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);

            return $response;
        } catch (\Throwable $e) {
            $this->telemetry->emit('report.export.completed', 'business', 'failure', [
                'company.id' => $companyId,
                'artifact_type' => $template->data_source,
                'format' => $format,
                'error.type' => $e::class,
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ], 'error');
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function exportGeneric(int $companyId, CompanyReportTemplate $template, array $parameters, string $format = 'xlsx'): BinaryFileResponse|\Illuminate\Http\Response
    {
        if ($format !== 'xlsx') {
            throw ValidationException::withMessages([
                'format' => 'Only Excel export is supported in this phase.',
            ]);
        }

        $normalized = $this->validatedConfig($template);
        $source = $this->resolveSource($template);
        $this->validateParameters($source, $parameters, $normalized);

        $query = $this->buildQuery($companyId, $source, $normalized, $parameters);
        $records = $query->get();
        $headings = $this->configNormalizer->headingsForColumns($normalized, $source->columns());
        $rows = $this->mapRows($source, $records, $normalized);

        $run = ReportRun::create([
            'company_id' => $companyId,
            'template_id' => $template->id,
            'parameters' => $parameters,
            'status' => ReportRunStatus::Completed,
            'output_format' => $format,
            'row_count' => count($rows),
            'requested_by' => Auth::id(),
            'completed_at' => now(),
        ]);

        $this->auditLogger->log(
            $run,
            AuditEventType::CREATE,
            null,
            [
                'template_id' => $template->id,
                'template_slug' => $template->slug,
                'parameters' => $parameters,
                'row_count' => count($rows),
            ],
            $companyId,
        );

        $filename = sprintf(
            '%s_%s.%s',
            $template->slug,
            now()->format('Y-m-d_His'),
            $format,
        );

        return Excel::download(new GenericReportExport($headings, $rows), $filename);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array{headings: list<string>, rows: list<list<mixed>>, total: int}
     */
    private function previewSalarySheet(
        int $companyId,
        CompanyReportTemplate $template,
        array $parameters,
        int $limit,
    ): array {
        $normalized = $this->validatedConfig($template);
        $source = $this->resolveSource($template);
        $this->validateParameters($source, $parameters, $normalized);

        $run = $this->resolvePayrollRun($companyId, (int) $parameters['payroll_run_id']);
        $groupBy = $this->resolveGroupBy($normalized, $parameters);
        $sheets = $this->salarySheetService->buildSheets($run, $groupBy);
        $columnKeys = $this->configNormalizer->visibleColumnKeys($normalized);
        $headings = $this->configNormalizer->headingsForColumns($normalized, $source->columns());

        $employees = collect($sheets)->flatMap(fn (array $sheet) => $sheet['employees'])->values();
        $total = $employees->count();
        $rows = $employees
            ->take($limit)
            ->map(fn (array $employee) => $this->mapSalarySheetPreviewRow($employee, $columnKeys, $normalized))
            ->all();

        return [
            'headings' => $headings,
            'rows' => $rows,
            'total' => $total,
        ];
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function recordSalarySheetExport(
        int $companyId,
        CompanyReportTemplate $template,
        array $parameters,
        string $format,
    ): void {
        $normalized = $this->validatedConfig($template);
        $source = $this->resolveSource($template);
        $this->validateParameters($source, $parameters, $normalized);

        $run = $this->resolvePayrollRun($companyId, (int) $parameters['payroll_run_id']);
        $groupBy = $this->resolveGroupBy($normalized, $parameters);
        $exportFormat = (string) ($parameters['format'] ?? $format ?: $template->default_format ?? 'pdf');
        $sheets = $this->salarySheetService->buildSheets($run, $groupBy);
        $rowCount = collect($sheets)->sum(fn (array $sheet) => count($sheet['employees']));

        $reportRun = ReportRun::create([
            'company_id' => $companyId,
            'template_id' => $template->id,
            'parameters' => $parameters,
            'status' => ReportRunStatus::Completed,
            'output_format' => $exportFormat,
            'row_count' => $rowCount,
            'requested_by' => Auth::id(),
            'completed_at' => now(),
        ]);

        $this->auditLogger->log(
            $reportRun,
            AuditEventType::CREATE,
            null,
            [
                'template_id' => $template->id,
                'template_slug' => $template->slug,
                'parameters' => $parameters,
                'row_count' => $rowCount,
            ],
            $companyId,
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function exportSalarySheet(
        int $companyId,
        CompanyReportTemplate $template,
        array $parameters,
        string $format,
    ): BinaryFileResponse|\Illuminate\Http\Response {
        $normalized = $this->validatedConfig($template);
        $source = $this->resolveSource($template);
        $this->validateParameters($source, $parameters, $normalized);

        $run = $this->resolvePayrollRun($companyId, (int) $parameters['payroll_run_id']);
        $groupBy = $this->resolveGroupBy($normalized, $parameters);
        $exportFormat = (string) ($parameters['format'] ?? $format ?: $template->default_format ?? 'pdf');

        $this->recordSalarySheetExport($companyId, $template, $parameters, $format);

        return $this->salarySheetExporter->download($run, $groupBy, $exportFormat);
    }

    private function resolvePayrollRun(int $companyId, int $payrollRunId): PayrollRun
    {
        return PayrollRun::query()
            ->where('company_id', $companyId)
            ->with('company')
            ->findOrFail($payrollRunId);
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @param  array<string, mixed>  $parameters
     */
    private function resolveGroupBy(array $normalized, array $parameters): string
    {
        if (isset($parameters['group_by']) && $parameters['group_by'] !== '') {
            return (string) $parameters['group_by'];
        }

        return (string) ($normalized['layout']['group_by'] ?? 'company');
    }

    /**
     * @param  array<string, mixed>  $employee
     * @param  list<string>  $columnKeys
     * @param  array<string, mixed>  $normalized
     * @return list<mixed>
     */
    private function mapSalarySheetPreviewRow(array $employee, array $columnKeys, array $normalized): array
    {
        $formats = $this->formatMap($normalized);

        return array_map(function (string $columnKey) use ($employee, $formats) {
            $value = match ($columnKey) {
                'employee.employee_code' => $employee['employee_code'] ?? null,
                'employee.employee_name' => $employee['employee_name'] ?? null,
                'employee.father_name' => $employee['father_name'] ?? null,
                'employee.department.department_name' => $employee['department_name'] ?? null,
                'employee.location.name' => $employee['location_name'] ?? null,
                'work_days' => $employee['work_days'] ?? null,
                'holiday_days' => $employee['holiday_days'] ?? null,
                'total_days' => $employee['total_days'] ?? null,
                'actual_gross' => $employee['actual_gross'] ?? null,
                'payable_gross' => $employee['payable_gross'] ?? null,
                'total_deductions' => $employee['total_deductions'] ?? null,
                'net_pay' => $employee['net_pay'] ?? null,
                default => null,
            };

            return $this->formatDisplayValue($value, $formats[$columnKey] ?? ReportColumnFormat::Text->value);
        }, $columnKeys);
    }

    public function resolveSource(CompanyReportTemplate $template): ReportDataSource
    {
        return $this->registry->get($template->data_source);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedConfig(CompanyReportTemplate $template): array
    {
        return $this->configValidator->validate(
            $template->data_source,
            is_array($template->config) ? $template->config : [],
        );
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @param  array<string, mixed>  $parameters
     */
    private function buildQuery(
        int $companyId,
        ReportDataSource $source,
        array $normalized,
        array $parameters,
    ) {
        $columnKeys = $this->configNormalizer->visibleColumnKeys($normalized);
        $query = $source->baseQuery($companyId);
        $query->with($source->eagerLoadsForColumns($columnKeys));
        $query = $source->applyFilters($query, $parameters, $normalized['filters'] ?? []);
        $query = $source->applySorting($query, $normalized['sort'] ?? []);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return list<list<mixed>>
     */
    private function mapRows(ReportDataSource $source, Collection $records, array $normalized): array
    {
        $columnKeys = $this->configNormalizer->visibleColumnKeys($normalized);
        $formats = $this->formatMap($normalized);

        return $records->map(function ($record) use ($source, $columnKeys, $formats) {
            return array_map(
                function (string $columnKey) use ($source, $record, $formats) {
                    $value = $source->resolveValue($record, $columnKey);

                    return $this->formatDisplayValue(
                        $value,
                        $formats[$columnKey] ?? ReportColumnFormat::Text->value,
                    );
                },
                $columnKeys,
            );
        })->all();
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return array<string, string>
     */
    private function formatMap(array $normalized): array
    {
        $map = [];
        foreach ($normalized['columns'] ?? [] as $column) {
            if (! is_array($column)) {
                continue;
            }
            $key = (string) ($column['key'] ?? '');
            if ($key !== '') {
                $map[$key] = (string) ($column['format'] ?? ReportColumnFormat::Text->value);
            }
        }

        return $map;
    }

    private function formatDisplayValue(mixed $value, string $format): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if ($format === ReportColumnFormat::Currency->value && is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $normalized
     */
    private function validateParameters(
        ReportDataSource $source,
        array $parameters,
        array $normalized,
    ): void {
        $errors = [];
        $parameterKeys = $normalized['parameters'] ?? [];

        foreach ($source->parameters() as $definition) {
            $key = $definition['key'];
            $required = (bool) ($definition['required'] ?? false);
            $value = $parameters[$key] ?? null;

            if ($required && ($value === null || $value === '')) {
                $errors[$key] = "{$definition['label']} is required.";
            }
        }

        foreach ($parameterKeys as $paramKey) {
            if (! array_key_exists($paramKey, $parameters) || $parameters[$paramKey] === null || $parameters[$paramKey] === '') {
                $matched = collect($source->parameters())->firstWhere('key', $paramKey);
                if ($matched && ($matched['required'] ?? false)) {
                    $errors[$paramKey] = "{$matched['label']} is required.";
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
