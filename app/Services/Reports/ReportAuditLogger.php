<?php

namespace App\Services\Reports;

use App\Enums\Payroll\AuditEventType;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ReportAuditLogger
{
    public function log(
        Model $model,
        AuditEventType $eventType,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $companyId = null,
    ): AuditLog {
        return AuditLog::create([
            'company_id' => $companyId ?? ($model->company_id ?? null),
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'event_type' => $eventType,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_by' => Auth::id(),
            'changed_at' => now(),
            'source' => 'report_generator',
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function logTemplateLifecycle(
        Model $template,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $companyId = null,
    ): AuditLog {
        return AuditLog::create([
            'company_id' => $companyId ?? ($template->company_id ?? null),
            'auditable_type' => $template->getMorphClass(),
            'auditable_id' => $template->getKey(),
            'event_type' => AuditEventType::UPDATE,
            'old_values' => $oldValues,
            'new_values' => array_merge($newValues ?? [], ['action' => $action]),
            'changed_by' => Auth::id(),
            'changed_at' => now(),
            'source' => 'report_generator',
        ]);
    }
}
