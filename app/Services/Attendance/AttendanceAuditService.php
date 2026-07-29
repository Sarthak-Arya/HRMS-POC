<?php

namespace App\Services\Attendance;

use App\Models\AttendanceAuditLog;
use App\Services\Observability\DomainTelemetry;
use Illuminate\Support\Facades\Auth;

class AttendanceAuditService
{
    public function __construct(
        private readonly DomainTelemetry $telemetry,
    ) {
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        int $companyId,
        string $entityType,
        ?int $entityId,
        string $action,
        ?array $before = null,
        ?array $after = null,
        array $metadata = [],
    ): AttendanceAuditLog {
        $eventName = match ($action) {
            'daily_saved' => 'attendance.daily.saved',
            'monthly_created', 'monthly_saved' => 'attendance.monthly.saved',
            'summary_locked' => 'attendance.month.locked',
            'policy_created', 'policy_updated', 'policy_deactivated' => 'attendance.policy.changed',
            default => 'attendance.'.$action,
        };

        $safeMeta = array_intersect_key($metadata, array_flip([
            'month', 'year', 'rows', 'row_count', 'processed_count', 'failed_count',
        ]));

        $this->telemetry->emit($eventName, 'business', 'success', array_merge([
            'company.id' => $companyId,
            'attendance.month' => $safeMeta['month'] ?? null,
            'attendance.year' => $safeMeta['year'] ?? null,
            'row_count' => $safeMeta['rows'] ?? $safeMeta['row_count'] ?? null,
        ], array_filter([
            'processed_count' => $safeMeta['processed_count'] ?? null,
            'failed_count' => $safeMeta['failed_count'] ?? null,
        ], static fn ($v) => $v !== null)));

        return AttendanceAuditLog::create([
            'company_id' => $companyId,
            'actor_user_id' => Auth::id(),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'before_json' => $before,
            'after_json' => $after,
            'metadata_json' => $metadata !== [] ? $metadata : null,
            'created_at' => now(),
        ]);
    }
}
