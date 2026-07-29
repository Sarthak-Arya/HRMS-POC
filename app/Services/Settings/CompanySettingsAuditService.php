<?php

namespace App\Services\Settings;

use App\Enums\Settings\CompanySettingsSection;
use App\Models\CompanySettingsAuditLog;
use App\Services\Observability\DomainTelemetry;

class CompanySettingsAuditService
{
    public function __construct(
        private readonly DomainTelemetry $telemetry,
    ) {
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>  $after
     */
    public function log(
        int $companyId,
        CompanySettingsSection $section,
        ?array $before,
        array $after,
        ?int $actorUserId = null,
        ?string $reason = null,
    ): CompanySettingsAuditLog {
        $this->telemetry->emit('settings.section.changed', 'audit', 'success', [
            'company.id' => $companyId,
            'section' => $section->value,
            'actor.user_id' => $actorUserId,
        ]);

        return CompanySettingsAuditLog::create([
            'company_id' => $companyId,
            'actor_user_id' => $actorUserId,
            'section' => $section->value,
            'before_json' => $before,
            'after_json' => $after,
            'reason' => $reason,
        ]);
    }
}
