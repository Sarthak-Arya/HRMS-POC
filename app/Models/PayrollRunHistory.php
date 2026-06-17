<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRunHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'payroll_run_history';

    protected $fillable = [
        'payroll_run_id',
        'version_no',
        'changed_by',
        'change_reason',
        'snapshot_json',
        'created_at',
    ];

    protected $casts = [
        'version_no' => 'integer',
        'snapshot_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
