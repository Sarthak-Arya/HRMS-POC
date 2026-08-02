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
        string $outcome = 'success',
        string $severity = 'info',
    ): AttendanceAuditLog {
        $eventName = match ($action) {
            'daily_saved' => 'attendance.daily.saved',
            'monthly_created', 'monthly_saved' => 'attendance.monthly.saved',
            'summary_locked' => 'attendance.month.locked',
            'policy_created', 'policy_updated', 'policy_deactivated' => 'attendance.policy.changed',
            'policy_assigned' => 'attendance.policy.assigned',
            'import_completed' => 'attendance.import.completed',
            'compoff_changed' => 'attendance.compoff.changed',
            'leave_exception_changed' => 'attendance.leave_exception.changed',
            'leave_type_changed' => 'attendance.leave_type.changed',
            'lock_failed' => 'attendance.month.lock_failed',
            default => 'attendance.'.$action,
        };

        $category = match ($action) {
            'policy_created',
            'policy_updated',
            'policy_deactivated',
            'policy_assigned',
            'leave_type_changed',
            'leave_exception_changed' => 'audit',
            default => 'business',
        };

        if ($action === 'lock_failed') {
            $outcome = 'failure';
            $severity = 'error';
        } elseif ($action === 'import_completed' && (int) ($metadata['failed_count'] ?? 0) > 0) {
            $outcome = 'failure';
            $severity = $severity === 'info' ? 'warning' : $severity;
        }

        $safeMeta = array_intersect_key($metadata, array_flip([
            'month', 'year', 'rows', 'row_count', 'processed_count', 'failed_count',
            'from_status', 'to_status', 'error.type', 'section',
        ]));

        $this->telemetry->emit($eventName, $category, $outcome, array_filter([
            'company.id' => $companyId,
            'attendance.month' => $safeMeta['month'] ?? null,
            'attendance.year' => $safeMeta['year'] ?? null,
            'row_count' => $safeMeta['rows'] ?? $safeMeta['row_count'] ?? null,
            'processed_count' => $safeMeta['processed_count'] ?? null,
            'failed_count' => $safeMeta['failed_count'] ?? null,
            'from_status' => $safeMeta['from_status'] ?? null,
            'to_status' => $safeMeta['to_status'] ?? null,
            'error.type' => $safeMeta['error.type'] ?? null,
            'section' => $safeMeta['section'] ?? null,
        ], static fn ($v) => $v !== null), $severity);

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
