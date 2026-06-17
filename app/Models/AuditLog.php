<?php

namespace App\Models;

use App\Enums\Payroll\AuditEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'company_id',
        'auditable_type',
        'auditable_id',
        'event_type',
        'old_values',
        'new_values',
        'changed_by',
        'changed_at',
        'request_id',
        'source',
    ];

    protected $casts = [
        'event_type' => AuditEventType::class,
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
