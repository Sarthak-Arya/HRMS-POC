<?php

namespace App\Services\Reports;

use App\Enums\Payroll\AuditEventType;
use App\Models\AuditLog;
use App\Services\Observability\DomainTelemetry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ReportAuditLogger
{
    public function __construct(
        private readonly DomainTelemetry $telemetry,
    ) {
    }

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
        $resolvedCompanyId = $companyId ?? ($template->company_id ?? null);

        $auditLog = AuditLog::create([
            'company_id' => $resolvedCompanyId,
            'auditable_type' => $template->getMorphClass(),
            'auditable_id' => $template->getKey(),
            'event_type' => AuditEventType::UPDATE,
            'old_values' => $oldValues,
            'new_values' => array_merge($newValues ?? [], ['action' => $action]),
            'changed_by' => Auth::id(),
            'changed_at' => now(),
            'source' => 'report_generator',
        ]);

        $slug = $template->slug ?? null;
        $dataSource = $template->data_source ?? null;
        $artifactType = filled($slug)
            ? (string) $slug
            : (filled($dataSource) ? (string) $dataSource : 'report_template');

        $context = [
            'artifact_type' => $artifactType,
        ];
        if ($resolvedCompanyId !== null) {
            $context['company.id'] = (int) $resolvedCompanyId;
        }

        $this->telemetry->emit('report.template.changed', 'audit', 'success', $context);

        return $auditLog;
    }
}
