<?php

namespace App\Services\Settings\Adapters;

use App\Enums\Settings\CompanySettingsSection;
use App\Services\Settings\CompanySettingsService;

class AttendanceSettingsAdapter
{
    public function __construct(
        private readonly CompanySettingsService $settingsService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function read(int $companyId): array
    {
        return $this->settingsService->getSection($companyId, CompanySettingsSection::Attendance);
    }

    public function shouldEnforceMonthLock(int $companyId): bool
    {
        return (bool) ($this->read($companyId)['monthLockEnforced'] ?? true);
    }

    public function defaultView(int $companyId): string
    {
        return (string) ($this->read($companyId)['defaultView'] ?? 'monthly_summary');
    }

    public function showDailyMarking(int $companyId): bool
    {
        return (bool) ($this->read($companyId)['showDailyMarking'] ?? true);
    }
}
