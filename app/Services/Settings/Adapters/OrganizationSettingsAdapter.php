<?php

namespace App\Services\Settings\Adapters;

use App\Enums\Settings\CompanySettingsSection;
use App\Services\Settings\CompanySettingsService;

class OrganizationSettingsAdapter
{
    public function __construct(
        private readonly CompanySettingsService $settingsService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function read(int $companyId): array
    {
        return $this->settingsService->getSection($companyId, CompanySettingsSection::Organization);
    }

    public function requiresDepartment(int $companyId): bool
    {
        return (bool) ($this->read($companyId)['requireDepartment'] ?? true);
    }

    public function requiresDesignation(int $companyId): bool
    {
        return (bool) ($this->read($companyId)['requireDesignation'] ?? true);
    }

    public function requiresLocation(int $companyId): bool
    {
        return (bool) ($this->read($companyId)['requireLocation'] ?? false);
    }
}
