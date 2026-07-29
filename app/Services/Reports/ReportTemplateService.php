<?php

namespace App\Services\Reports;

use App\Models\CompanyReportTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReportTemplateService
{
    public function __construct(
        private readonly ReportTemplateConfigValidator $configValidator,
        private readonly ReportTemplateConfigNormalizer $configNormalizer,
        private readonly ReportDataSourceRegistry $registry,
        private readonly ReportAuditLogger $auditLogger,
    ) {}

    public function findForCompany(int $companyId, int $templateId): CompanyReportTemplate
    {
        $template = CompanyReportTemplate::query()
            ->where('id', $templateId)
            ->where('is_active', true)
            ->where(function ($query) use ($companyId) {
                $query->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            })
            ->first();

        if ($template === null) {
            throw ValidationException::withMessages([
                'templateId' => 'Report template not found or not available for this company.',
            ]);
        }

        return $template;
    }

    /**
     * @return Collection<int, CompanyReportTemplate>
     */
    public function listForCompany(int $companyId): Collection
    {
        return CompanyReportTemplate::query()
            ->where('is_active', true)
            ->where(function ($query) use ($companyId) {
                $query->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{system: Collection<int, CompanyReportTemplate>, company: Collection<int, CompanyReportTemplate>}
     */
    public function listManageableForCompany(int $companyId): array
    {
        $system = CompanyReportTemplate::query()
            ->whereNull('company_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $company = CompanyReportTemplate::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return compact('system', 'company');
    }

    public function findOwnedByCompany(int $companyId, int $templateId): CompanyReportTemplate
    {
        $template = CompanyReportTemplate::query()
            ->where('id', $templateId)
            ->where('company_id', $companyId)
            ->first();

        if ($template === null) {
            throw ValidationException::withMessages([
                'templateId' => 'Company report template not found.',
            ]);
        }

        return $template;
    }

    public function findSystemTemplate(int $templateId): CompanyReportTemplate
    {
        $template = CompanyReportTemplate::query()
            ->where('id', $templateId)
            ->whereNull('company_id')
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            throw ValidationException::withMessages([
                'sourceTemplateId' => 'System template not found.',
            ]);
        }

        return $template;
    }

    /**
     * Create a company-owned template from a curated data source.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createCompanyTemplate(int $companyId, array $payload): CompanyReportTemplate
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $dataSource = (string) ($payload['data_source'] ?? '');

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Template name is required.',
            ]);
        }

        try {
            $this->registry->get($dataSource);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages([
                'data_source' => 'Invalid report data source.',
            ]);
        }

        $config = $payload['config'] ?? $this->configNormalizer->defaultConfigForSource($dataSource);
        $normalized = $this->configValidator->validate($dataSource, is_array($config) ? $config : []);

        $defaultFormat = (string) ($payload['default_format']
            ?? ($dataSource === 'salary_sheet' ? 'pdf' : 'xlsx'));

        if (! in_array($defaultFormat, ['xlsx', 'pdf'], true)) {
            throw ValidationException::withMessages([
                'default_format' => 'Default format must be xlsx or pdf.',
            ]);
        }

        $slug = $this->generateCompanySlug(Str::slug($name) ?: $dataSource, $companyId);

        $template = CompanyReportTemplate::create([
            'company_id' => $companyId,
            'name' => $name,
            'slug' => $slug,
            'data_source' => $dataSource,
            'config' => $normalized,
            'default_format' => $defaultFormat,
            'is_active' => true,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->auditLogger->logTemplateLifecycle(
            $template,
            'template_created',
            null,
            [
                'name' => $template->name,
                'data_source' => $template->data_source,
                'company_id' => $companyId,
            ],
            $companyId,
        );

        return $template;
    }

    /**
     * Clone a system template into an independent company-owned copy.
     * Never mutates the system source.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function cloneSystemTemplate(int $companyId, int $sourceTemplateId, array $overrides = []): CompanyReportTemplate
    {
        $source = $this->findSystemTemplate($sourceTemplateId);

        $name = (string) ($overrides['name'] ?? $source->name.' (Custom)');
        $config = $overrides['config'] ?? $source->config;
        $normalized = $this->configValidator->validate($source->data_source, is_array($config) ? $config : []);

        $slug = $this->generateCompanySlug($source->slug, $companyId);

        $template = CompanyReportTemplate::create([
            'company_id' => $companyId,
            'name' => $name,
            'slug' => $slug,
            'data_source' => $source->data_source,
            'config' => $normalized,
            'default_format' => $source->default_format,
            'is_active' => true,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->auditLogger->logTemplateLifecycle(
            $template,
            'template_cloned',
            null,
            [
                'source_template_id' => $source->id,
                'source_slug' => $source->slug,
                'company_id' => $companyId,
            ],
            $companyId,
        );

        return $template;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateCompanyTemplate(int $companyId, int $templateId, array $payload): CompanyReportTemplate
    {
        $template = $this->findOwnedByCompany($companyId, $templateId);

        if ($template->isGlobal()) {
            throw ValidationException::withMessages([
                'templateId' => 'System templates cannot be modified.',
            ]);
        }

        $before = $template->only(['name', 'config', 'is_active', 'default_format']);
        $updates = [];

        if (array_key_exists('name', $payload)) {
            $updates['name'] = (string) $payload['name'];
        }

        if (array_key_exists('is_active', $payload)) {
            $updates['is_active'] = (bool) $payload['is_active'];
        }

        if (array_key_exists('default_format', $payload)) {
            $format = (string) $payload['default_format'];
            if (! in_array($format, ['xlsx', 'pdf'], true)) {
                throw ValidationException::withMessages([
                    'default_format' => 'Default format must be xlsx or pdf.',
                ]);
            }
            $updates['default_format'] = $format;
        }

        if (array_key_exists('config', $payload)) {
            $updates['config'] = $this->configValidator->validate(
                $template->data_source,
                is_array($payload['config']) ? $payload['config'] : [],
            );
        }

        // data_source is immutable after create
        if (array_key_exists('data_source', $payload)
            && (string) $payload['data_source'] !== $template->data_source
        ) {
            throw ValidationException::withMessages([
                'data_source' => 'Data source cannot be changed after the template is created.',
            ]);
        }

        if ($updates === []) {
            return $template;
        }

        $template->fill($updates);
        $template->updated_by = Auth::id();
        $template->save();

        $this->auditLogger->logTemplateLifecycle(
            $template->fresh(),
            'template_updated',
            $before,
            $template->only(['name', 'config', 'is_active', 'default_format']),
            $companyId,
        );

        return $template->fresh();
    }

    public function archiveCompanyTemplate(int $companyId, int $templateId): CompanyReportTemplate
    {
        $template = $this->findOwnedByCompany($companyId, $templateId);

        if ($template->isGlobal()) {
            throw ValidationException::withMessages([
                'templateId' => 'System templates cannot be archived.',
            ]);
        }

        $before = $template->only(['name', 'is_active']);

        $template->is_active = false;
        $template->updated_by = Auth::id();
        $template->save();

        $this->auditLogger->logTemplateLifecycle(
            $template,
            'template_archived',
            $before,
            ['is_active' => false],
            $companyId,
        );

        return $template;
    }

    private function generateCompanySlug(string $sourceSlug, int $companyId): string
    {
        $base = Str::slug($sourceSlug).'-c'.$companyId;
        $slug = $base;
        $suffix = 1;

        while (CompanyReportTemplate::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
