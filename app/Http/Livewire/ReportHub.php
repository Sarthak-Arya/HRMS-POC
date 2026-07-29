<?php

namespace App\Http\Livewire;

use App\Enums\Reports\ReportColumnFormat;
use App\Enums\Reports\ReportRunStatus;
use App\Models\Company;
use App\Models\Department;
use App\Models\PayrollRun;
use App\Models\ReportRun;
use App\Services\Reports\ReportDataSourceRegistry;
use App\Services\Reports\ReportRunnerService;
use App\Services\Reports\ReportTemplateConfigNormalizer;
use App\Services\Reports\ReportTemplateConfigValidator;
use App\Services\Reports\ReportTemplateService;
use App\Services\Settings\Adapters\ReportsSettingsAdapter;
use Livewire\Component;

class ReportHub extends Component
{
    public string $companyId = '';

    public string $activeTab = 'run';

    public string $filterCategory = 'all';

    public ?int $selectedTemplateId = null;

    public string $departmentId = '';

    public int $month = 0;

    public int $year = 0;

    public ?int $payrollRunId = null;

    public string $groupBy = 'company';

    public string $exportFormat = 'pdf';

    /** @var list<string> */
    public array $previewHeadings = [];

    /** @var list<list<mixed>> */
    public array $previewRows = [];

    public int $previewTotal = 0;

    public bool $hasPreview = false;

    public bool $showEditModal = false;

    public bool $isCreating = false;

    public ?int $editTemplateId = null;

    public string $editName = '';

    public string $editDataSource = '';

    public bool $editIsActive = true;

    public string $editDefaultFormat = 'xlsx';

    public string $editGroupBy = 'company';

    /** @var list<string> */
    public array $editColumns = [];

    /** @var array<string, string> */
    public array $editColumnLabels = [];

    /** @var array<string, string> */
    public array $editColumnFormats = [];

    /** @var list<array{field: string, op: string, param: string}> */
    public array $editFilters = [];

    public string $editSortField = '';

    public string $editSortDir = 'asc';

    /** @var list<string> */
    public array $editParameters = [];

    public function mount(?string $company_id = null, ?ReportsSettingsAdapter $reportsSettings = null): void
    {
        $this->companyId = (string) ($company_id ?? session('companyId'));
        session()->put('companyId', $this->companyId);

        $now = now();
        $this->month = (int) $now->month;
        $this->year = (int) $now->year;

        if ($this->companyId !== '') {
            $adapter = $reportsSettings ?? app(ReportsSettingsAdapter::class);
            $this->filterCategory = $adapter->defaultTemplateCategory((int) $this->companyId);
        }
    }

    public function updatedSelectedTemplateId(): void
    {
        $this->resetPreview();
        $this->departmentId = '';
        $this->payrollRunId = null;
        $this->groupBy = 'company';
        $this->exportFormat = 'pdf';
    }

    public function updatedEditDataSource(): void
    {
        if (! $this->isCreating || $this->editDataSource === '') {
            return;
        }

        $this->hydrateBuilderFromDefault($this->editDataSource);
    }

    public function updatedEditColumns(): void
    {
        $this->ensureMandatoryColumnsSelected();
        $this->ensureRequiredParametersSelected();
    }

    public function updatedEditParameters(): void
    {
        $this->ensureRequiredParametersSelected();
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function setFilterCategory(string $category): void
    {
        $this->filterCategory = $category;
    }

    public function selectTemplate(int $templateId): void
    {
        $this->selectedTemplateId = $templateId;
        $this->updatedSelectedTemplateId();
    }

    public function quickPreview(
        int $templateId,
        ReportTemplateService $templateService,
        ReportRunnerService $runnerService,
    ): void {
        $this->selectTemplate($templateId);
        $this->previewReport($templateService, $runnerService);
    }

    public function quickDownload(
        int $templateId,
        ReportTemplateService $templateService,
        ReportRunnerService $runnerService,
    ) {
        $this->selectTemplate($templateId);

        return $this->downloadReport($templateService, $runnerService);
    }

    /**
     * @return array{icon: string, category: string, description: string, accent: string}
     */
    public static function templateMeta(string $dataSource): array
    {
        return match ($dataSource) {
            'payroll_register' => [
                'icon' => 'receipt_long',
                'category' => 'payroll',
                'description' => 'Complete breakdown of gross pay, deductions, and net pay for a payroll run.',
                'accent' => 'primary',
            ],
            'salary_sheet' => [
                'icon' => 'payments',
                'category' => 'payroll',
                'description' => 'Monthly salary register with actual vs payable amounts, statutory summary, PDF or Excel.',
                'accent' => 'primary',
            ],
            'attendance_monthly' => [
                'icon' => 'event_available',
                'category' => 'attendance',
                'description' => 'Monthly attendance summary with present days, leave, LOP, and overtime.',
                'accent' => 'success',
            ],
            default => [
                'icon' => 'groups',
                'category' => 'employee',
                'description' => 'Employee master data with department, designation, and statutory details.',
                'accent' => 'info',
            ],
        };
    }

    public function previewReport(
        ReportTemplateService $templateService,
        ReportRunnerService $runnerService,
    ): void {
        $this->resetPreview();

        if ($this->selectedTemplateId === null) {
            session()->flash('error', 'Please select a report template.');

            return;
        }

        try {
            $template = $templateService->findForCompany((int) $this->companyId, $this->selectedTemplateId);
            $result = $runnerService->preview(
                (int) $this->companyId,
                $template,
                $this->buildParameters(),
            );

            $this->previewHeadings = $result['headings'];
            $this->previewRows = $result['rows'];
            $this->previewTotal = $result['total'];
            $this->hasPreview = true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        }
    }

    public function downloadReport(
        ReportTemplateService $templateService,
        ReportRunnerService $runnerService,
    ) {
        if (! auth()->user()?->hasPermission('reports.run')) {
            abort(403, 'You do not have permission to download reports.');
        }

        if ($this->selectedTemplateId === null) {
            session()->flash('error', 'Please select a report template.');

            return null;
        }

        try {
            $template = $templateService->findForCompany((int) $this->companyId, $this->selectedTemplateId);
            $params = $this->buildParameters();
            $format = $this->resolveExportFormat($template);

            if ($template->data_source === 'salary_sheet') {
                $runnerService->recordSalarySheetExport(
                    (int) $this->companyId,
                    $template,
                    $params,
                    $format,
                );

                return redirect()->route('reports.salary-sheet', [
                    'company_id' => $this->companyId,
                    'run_id' => $this->payrollRunId,
                    'group_by' => $this->groupBy,
                    'format' => $format,
                ]);
            }

            return $runnerService->export(
                (int) $this->companyId,
                $template,
                $params,
                $format,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return null;
        }
    }

    public function openCreateModal(): void
    {
        $this->authorizeManage();
        $this->resetBuilderState();
        $this->isCreating = true;
        $this->showEditModal = true;
        $this->editName = '';
        $this->editDataSource = 'employees';
        $this->editIsActive = true;
        $this->hydrateBuilderFromDefault('employees');
        $this->resetValidation();
    }

    public function cloneTemplate(int $sourceTemplateId, ReportTemplateService $templateService): void
    {
        $this->authorizeManage();

        try {
            $template = $templateService->cloneSystemTemplate((int) $this->companyId, $sourceTemplateId);
            session()->flash('success', "Custom template \"{$template->name}\" created from system template.");
            $this->openEditModal($template->id, $templateService);
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        }
    }

    public function openEditModal(int $templateId, ReportTemplateService $templateService): void
    {
        $this->authorizeManage();

        $template = $templateService->findOwnedByCompany((int) $this->companyId, $templateId);
        $normalized = app(ReportTemplateConfigNormalizer::class)->normalize(
            $template->data_source,
            is_array($template->config) ? $template->config : [],
        );

        $this->isCreating = false;
        $this->editTemplateId = $template->id;
        $this->editName = $template->name;
        $this->editDataSource = $template->data_source;
        $this->editIsActive = (bool) $template->is_active;
        $this->editDefaultFormat = $template->default_format ?: 'xlsx';
        $this->editGroupBy = (string) ($normalized['layout']['group_by'] ?? 'company');
        $this->hydrateBuilderFromNormalized($normalized);
        $this->showEditModal = true;
        $this->resetValidation();
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->isCreating = false;
        $this->resetBuilderState();
        $this->resetValidation();
    }

    public function moveColumnUp(string $columnKey): void
    {
        $index = array_search($columnKey, $this->editColumns, true);
        if ($index === false || $index === 0) {
            return;
        }

        $swap = $this->editColumns[$index - 1];
        $this->editColumns[$index - 1] = $columnKey;
        $this->editColumns[$index] = $swap;
        $this->editColumns = array_values($this->editColumns);
    }

    public function moveColumnDown(string $columnKey): void
    {
        $index = array_search($columnKey, $this->editColumns, true);
        if ($index === false || $index >= count($this->editColumns) - 1) {
            return;
        }

        $swap = $this->editColumns[$index + 1];
        $this->editColumns[$index + 1] = $columnKey;
        $this->editColumns[$index] = $swap;
        $this->editColumns = array_values($this->editColumns);
    }

    public function addFilter(): void
    {
        $metadata = $this->builderMetadata;
        $field = $metadata['filters'][0] ?? '';
        $op = $metadata['operators'][0] ?? '=';
        $param = $this->defaultParamForFilterField($field);

        if ($field === '') {
            return;
        }

        $this->editFilters[] = [
            'field' => $field,
            'op' => $op,
            'param' => $param,
        ];
    }

    public function removeFilter(int $index): void
    {
        unset($this->editFilters[$index]);
        $this->editFilters = array_values($this->editFilters);
    }

    public function saveTemplate(ReportTemplateService $templateService): void
    {
        $this->authorizeManage();

        $this->validate([
            'editName' => 'required|string|max:255',
            'editColumns' => 'required|array|min:1',
            'editDataSource' => 'required|string',
        ]);

        try {
            $config = $this->buildConfigFromBuilder();

            if ($this->isCreating) {
                $template = $templateService->createCompanyTemplate((int) $this->companyId, [
                    'name' => $this->editName,
                    'data_source' => $this->editDataSource,
                    'default_format' => $this->editDefaultFormat,
                    'config' => $config,
                ]);
                session()->flash('success', "Template \"{$template->name}\" created successfully.");
            } else {
                if ($this->editTemplateId === null) {
                    return;
                }

                $templateService->updateCompanyTemplate((int) $this->companyId, $this->editTemplateId, [
                    'name' => $this->editName,
                    'is_active' => $this->editIsActive,
                    'default_format' => $this->editDefaultFormat,
                    'config' => $config,
                ]);
                session()->flash('success', 'Template updated successfully.');
            }

            $this->closeEditModal();
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        }
    }

    public function archiveTemplate(int $templateId, ReportTemplateService $templateService): void
    {
        $this->authorizeManage();

        try {
            $template = $templateService->archiveCompanyTemplate((int) $this->companyId, $templateId);

            if ($this->selectedTemplateId === $templateId) {
                $this->selectedTemplateId = null;
                $this->resetPreview();
            }

            session()->flash('success', "Template \"{$template->name}\" archived.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildParameters(): array
    {
        $params = [];

        if ($this->departmentId !== '') {
            $params['department_id'] = (int) $this->departmentId;
        }

        if ($this->month > 0) {
            $params['month'] = $this->month;
        }

        if ($this->year > 0) {
            $params['year'] = $this->year;
        }

        if ($this->payrollRunId !== null) {
            $params['payroll_run_id'] = $this->payrollRunId;
        }

        if ($this->selectedTemplate?->data_source === 'salary_sheet') {
            $params['group_by'] = $this->groupBy;
            $params['format'] = $this->exportFormat;
        }

        return $params;
    }

    private function resolveExportFormat($template): string
    {
        if ($template->data_source === 'salary_sheet') {
            return $this->exportFormat !== '' ? $this->exportFormat : 'pdf';
        }

        return 'xlsx';
    }

    public function getIsSalarySheetTemplateProperty(): bool
    {
        return $this->selectedTemplate?->data_source === 'salary_sheet';
    }

    public function getDownloadButtonLabelProperty(): string
    {
        if ($this->isSalarySheetTemplate) {
            return $this->exportFormat === 'pdf' ? 'Download PDF' : 'Download Excel';
        }

        return 'Download Excel';
    }

    private function resetPreview(): void
    {
        $this->previewHeadings = [];
        $this->previewRows = [];
        $this->previewTotal = 0;
        $this->hasPreview = false;
        $this->resetValidation();
    }

    private function authorizeManage(): void
    {
        if (! auth()->user()?->hasPermission('reports.manage')) {
            abort(403, 'You do not have permission to manage report templates.');
        }
    }

    public function getCanManageTemplatesProperty(): bool
    {
        return auth()->user()?->hasPermission('reports.manage') ?? false;
    }

    public function getSelectedTemplateProperty()
    {
        if ($this->selectedTemplateId === null) {
            return null;
        }

        return app(ReportTemplateService::class)
            ->listForCompany((int) $this->companyId)
            ->firstWhere('id', $this->selectedTemplateId);
    }

    /**
     * @return list<array{key: string, label: string, type: string, required?: bool}>
     */
    public function getActiveParametersProperty(): array
    {
        $template = $this->selectedTemplate;

        if ($template === null) {
            return [];
        }

        $source = app(ReportDataSourceRegistry::class)->get($template->data_source);
        $enabled = $template->parameterKeys();

        return array_values(array_filter(
            $source->parameters(),
            fn (array $definition) => in_array($definition['key'], $enabled, true)
                || ($definition['required'] ?? false),
        ));
    }

    /**
     * @return array<string, string>
     */
    public function getEditAvailableColumnsProperty(): array
    {
        if ($this->editDataSource === '') {
            return [];
        }

        return app(ReportTemplateConfigValidator::class)
            ->availableColumns($this->editDataSource);
    }

    /**
     * @return array{
     *     columns: array<string, array{label: string, format: string}>,
     *     filters: list<string>,
     *     operators: list<string>,
     *     sortable: list<string>,
     *     parameters: list<array{key: string, label: string, type: string, required?: bool}>,
     *     mandatory_columns: list<string>,
     *     group_by_options: list<string>
     * }
     */
    public function getBuilderMetadataProperty(): array
    {
        if ($this->editDataSource === '') {
            return [
                'columns' => [],
                'filters' => [],
                'operators' => [],
                'sortable' => [],
                'parameters' => [],
                'mandatory_columns' => [],
                'group_by_options' => [],
            ];
        }

        return app(ReportTemplateConfigValidator::class)->builderMetadata($this->editDataSource);
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function getAvailableDataSourcesProperty(): array
    {
        return array_map(
            fn ($source) => [
                'key' => $source->key()->value,
                'label' => $source->label(),
            ],
            app(ReportDataSourceRegistry::class)->all(),
        );
    }

    /**
     * @return list<string>
     */
    public function getAvailableFormatsProperty(): array
    {
        return ReportColumnFormat::values();
    }

    private function resetBuilderState(): void
    {
        $this->editTemplateId = null;
        $this->editName = '';
        $this->editDataSource = '';
        $this->editIsActive = true;
        $this->editDefaultFormat = 'xlsx';
        $this->editGroupBy = 'company';
        $this->editColumns = [];
        $this->editColumnLabels = [];
        $this->editColumnFormats = [];
        $this->editFilters = [];
        $this->editSortField = '';
        $this->editSortDir = 'asc';
        $this->editParameters = [];
    }

    private function hydrateBuilderFromDefault(string $dataSource): void
    {
        $normalized = app(ReportTemplateConfigNormalizer::class)->defaultConfigForSource($dataSource);
        $this->editDefaultFormat = $dataSource === 'salary_sheet' ? 'pdf' : 'xlsx';
        $this->editGroupBy = (string) ($normalized['layout']['group_by'] ?? 'company');
        $this->hydrateBuilderFromNormalized($normalized);
    }

    /**
     * @param  array<string, mixed>  $normalized
     */
    private function hydrateBuilderFromNormalized(array $normalized): void
    {
        $this->editColumns = [];
        $this->editColumnLabels = [];
        $this->editColumnFormats = [];

        foreach ($normalized['columns'] ?? [] as $column) {
            if (! is_array($column) || ($column['visible'] ?? true) === false) {
                continue;
            }
            $key = (string) ($column['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $this->editColumns[] = $key;
            $this->editColumnLabels[$key] = (string) ($column['label'] ?? '');
            $this->editColumnFormats[$key] = (string) ($column['format'] ?? ReportColumnFormat::Text->value);
        }

        $this->editFilters = [];
        foreach ($normalized['filters'] ?? [] as $filter) {
            if (! is_array($filter)) {
                continue;
            }
            $field = (string) ($filter['field'] ?? '');
            if ($field === '') {
                continue;
            }
            $this->editFilters[] = [
                'field' => $field,
                'op' => (string) ($filter['op'] ?? '='),
                'param' => (string) ($filter['param'] ?? $this->defaultParamForFilterField($field)),
            ];
        }

        $sort = $normalized['sort'][0] ?? null;
        $this->editSortField = is_array($sort) ? (string) ($sort['field'] ?? '') : '';
        $this->editSortDir = is_array($sort) && strtolower((string) ($sort['dir'] ?? 'asc')) === 'desc'
            ? 'desc'
            : 'asc';

        $this->editParameters = array_values(array_map('strval', $normalized['parameters'] ?? []));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildConfigFromBuilder(): array
    {
        $metadata = $this->builderMetadata;
        $columns = [];

        foreach (array_values($this->editColumns) as $sequence => $key) {
            $defaultLabel = $metadata['columns'][$key]['label'] ?? $key;
            $customLabel = trim((string) ($this->editColumnLabels[$key] ?? ''));
            $columns[] = [
                'key' => $key,
                'label' => $customLabel !== '' && $customLabel !== $defaultLabel ? $customLabel : null,
                'format' => (string) ($this->editColumnFormats[$key]
                    ?? $metadata['columns'][$key]['format']
                    ?? ReportColumnFormat::Text->value),
                'visible' => true,
                'sequence' => $sequence,
            ];
        }

        // Ensure mandatory salary-sheet columns remain present even if unchecked.
        foreach ($metadata['mandatory_columns'] as $mandatoryKey) {
            if (! in_array($mandatoryKey, $this->editColumns, true)) {
                $columns[] = [
                    'key' => $mandatoryKey,
                    'label' => null,
                    'format' => $metadata['columns'][$mandatoryKey]['format'] ?? ReportColumnFormat::Text->value,
                    'visible' => true,
                    'sequence' => count($columns),
                ];
            }
        }

        $filters = [];
        foreach (array_values($this->editFilters) as $sequence => $filter) {
            $filters[] = [
                'field' => (string) ($filter['field'] ?? ''),
                'op' => (string) ($filter['op'] ?? '='),
                'param' => (string) ($filter['param'] ?? ''),
                'sequence' => $sequence,
            ];
        }

        $sort = [];
        if ($this->editSortField !== '') {
            $sort[] = [
                'field' => $this->editSortField,
                'dir' => $this->editSortDir === 'desc' ? 'desc' : 'asc',
            ];
        }

        $parameters = array_values($this->editParameters);
        foreach ($metadata['parameters'] as $definition) {
            if (($definition['required'] ?? false) && ! in_array($definition['key'], $parameters, true)) {
                $parameters[] = $definition['key'];
            }
        }

        $config = [
            'columns' => $columns,
            'filters' => $filters,
            'sort' => $sort,
            'parameters' => $parameters,
            'layout' => [],
        ];

        if ($metadata['group_by_options'] !== []) {
            $config['layout']['group_by'] = $this->editGroupBy !== ''
                ? $this->editGroupBy
                : 'company';
        }

        return $config;
    }

    private function defaultParamForFilterField(string $field): string
    {
        return match ($field) {
            'employee.department_id' => 'department_id',
            default => $field,
        };
    }

    private function ensureMandatoryColumnsSelected(): void
    {
        if ($this->editDataSource === '') {
            return;
        }

        $mandatory = $this->builderMetadata['mandatory_columns'] ?? [];
        foreach ($mandatory as $key) {
            if (! in_array($key, $this->editColumns, true)) {
                $this->editColumns[] = $key;
            }
        }
        $this->editColumns = array_values($this->editColumns);
    }

    private function ensureRequiredParametersSelected(): void
    {
        if ($this->editDataSource === '') {
            return;
        }

        foreach ($this->builderMetadata['parameters'] ?? [] as $definition) {
            if (($definition['required'] ?? false) && ! in_array($definition['key'], $this->editParameters, true)) {
                $this->editParameters[] = $definition['key'];
            }
        }
        $this->editParameters = array_values($this->editParameters);
    }

    public function render(ReportTemplateService $templateService)
    {
        $manageable = $templateService->listManageableForCompany((int) $this->companyId);
        $templates = $templateService->listForCompany((int) $this->companyId);
        $filteredTemplates = $templates->filter(function ($template) {
            if ($this->filterCategory === 'all') {
                return true;
            }

            return self::templateMeta($template->data_source)['category'] === $this->filterCategory;
        })->values();

        $lastRuns = ReportRun::query()
            ->where('company_id', $this->companyId)
            ->where('status', ReportRunStatus::Completed)
            ->orderByDesc('completed_at')
            ->get()
            ->unique('template_id')
            ->keyBy('template_id');

        $company = Company::query()->find($this->companyId);

        return view('livewire.report-hub', [
            'templates' => $templates,
            'filteredTemplates' => $filteredTemplates,
            'featuredTemplates' => $filteredTemplates->take(3),
            'lastRuns' => $lastRuns,
            'completedRunsCount' => ReportRun::query()
                ->where('company_id', $this->companyId)
                ->where('status', ReportRunStatus::Completed)
                ->count(),
            'companyName' => $company?->company_name ?? 'Company',
            'systemTemplates' => $manageable['system'],
            'companyTemplates' => $manageable['company'],
            'departments' => Department::query()
                ->where('company_id', $this->companyId)
                ->orderBy('department_name')
                ->get(),
            'payrollRuns' => PayrollRun::query()
                ->where('company_id', $this->companyId)
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get(),
        ]);
    }
}
