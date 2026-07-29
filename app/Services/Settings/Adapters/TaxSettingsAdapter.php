<?php

namespace App\Services\Settings\Adapters;

use App\Enums\Settings\CompanySettingsSection;
use App\Services\Settings\CompanySettingsService;

class TaxSettingsAdapter
{
    public function __construct(
        private readonly CompanySettingsService $settingsService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function read(int $companyId): array
    {
        return $this->settingsService->getSection($companyId, CompanySettingsSection::Tax);
    }

    public function pan(int $companyId): ?string
    {
        $value = $this->read($companyId)['pan'] ?? null;

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    public function tan(int $companyId): ?string
    {
        $value = $this->read($companyId)['tan'] ?? null;

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    public function taxPaymentFrequency(int $companyId): string
    {
        return (string) ($this->read($companyId)['taxPaymentFrequency'] ?? 'monthly');
    }

    public function deductorType(int $companyId): string
    {
        return (string) ($this->read($companyId)['deductorType'] ?? 'employee');
    }
}
