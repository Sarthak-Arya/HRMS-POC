<?php

namespace App\Services\Settings\Adapters;

use App\Enums\Settings\CompanySettingsSection;
use App\Services\Settings\CompanySettingsService;

class ReportsSettingsAdapter
{
    public function __construct(
        private readonly CompanySettingsService $settingsService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function read(int $companyId): array
    {
        return $this->settingsService->getSection($companyId, CompanySettingsSection::Reports);
    }

    public function defaultExportFormat(int $companyId): string
    {
        return (string) ($this->read($companyId)['defaultExportFormat'] ?? 'xlsx');
    }

    public function includeCompanyLogo(int $companyId): bool
    {
        return (bool) ($this->read($companyId)['includeCompanyLogo'] ?? false);
    }

    public function defaultTemplateCategory(int $companyId): string
    {
        return (string) ($this->read($companyId)['defaultTemplateCategory'] ?? 'all');
    }
}
