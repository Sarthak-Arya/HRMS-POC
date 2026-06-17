<?php

namespace App\Models;

use App\Enums\Payroll\PayrollLineComponentType;
use App\Services\Payroll\PayrollRunLifecycle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeePayrollLine extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $line): void {
            app(PayrollRunLifecycle::class)->assertChildWritable($line);
        });

        static::deleting(function (self $line): void {
            app(PayrollRunLifecycle::class)->assertChildWritable($line);
        });
    }

    protected $fillable = [
        'employee_payroll_id',
        'component_id',
        'component_name',
        'component_type',
        'calculated_amount',
        'calculation_basis',
    ];

    protected $casts = [
        'component_type' => PayrollLineComponentType::class,
        'calculated_amount' => 'decimal:2',
        'calculation_basis' => 'array',
    ];

    public function employeePayroll(): BelongsTo
    {
        return $this->belongsTo(EmployeePayroll::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(CompensationComponent::class, 'component_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(EmployeePayrollLineHistory::class)->orderByDesc('version_no');
    }
}
