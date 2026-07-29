<?php

namespace App\Services\Settings\Adapters;

use App\Enums\Settings\CompanySettingsSection;
use App\Services\Settings\CompanySettingsService;

class CompensationSettingsAdapter
{
    public function __construct(
        private readonly CompanySettingsService $settingsService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function read(int $companyId): array
    {
        return $this->settingsService->getSection($companyId, CompanySettingsSection::Compensation);
    }

    public function defaultPayCycle(int $companyId): string
    {
        return (string) ($this->read($companyId)['defaultPayCycle'] ?? 'monthly');
    }

    public function roundPayrollToNearest(int $companyId): int
    {
        return (int) ($this->read($companyId)['roundPayrollToNearest'] ?? 1);
    }

    public function showInactiveComponents(int $companyId): bool
    {
        return (bool) ($this->read($companyId)['showInactiveComponents'] ?? false);
    }
}
