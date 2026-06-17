<?php

namespace App\Services\Payroll;

use App\Enums\Payroll\AuditEventType;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PayrollAuditLogger
{
    public function log(
        Model $model,
        AuditEventType $eventType,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $companyId = null,
        ?string $source = null,
        ?string $requestId = null,
    ): AuditLog {
        return AuditLog::create([
            'company_id' => $companyId ?? $this->resolveCompanyId($model),
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'event_type' => $eventType,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_by' => Auth::id(),
            'changed_at' => now(),
            'request_id' => $requestId,
            'source' => $source ?? 'payroll_v2',
        ]);
    }

    private function resolveCompanyId(Model $model): ?int
    {
        if (isset($model->company_id)) {
            return (int) $model->company_id;
        }

        if (method_exists($model, 'payrollRun') && $model->relationLoaded('payrollRun')) {
            return $model->payrollRun?->company_id;
        }

        if (method_exists($model, 'employee') && $model->relationLoaded('employee')) {
            return $model->employee?->company_id;
        }

        return null;
    }
}
