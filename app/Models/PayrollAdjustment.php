<?php

namespace App\Models;

use App\Enums\Payroll\PayrollAdjustmentType;
use App\Services\Payroll\PayrollRunLifecycle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAdjustment extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $adjustment): void {
            app(PayrollRunLifecycle::class)->assertChildWritable($adjustment);
        });

        static::deleting(function (self $adjustment): void {
            app(PayrollRunLifecycle::class)->assertChildWritable($adjustment);
        });
    }

    protected $fillable = [
        'employee_id',
        'payroll_run_id',
        'component_id',
        'adjustment_type',
        'amount',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'adjustment_type' => PayrollAdjustmentType::class,
        'amount' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(CompensationComponent::class, 'component_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
