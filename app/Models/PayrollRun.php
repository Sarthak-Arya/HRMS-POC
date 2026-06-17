<?php

namespace App\Models;

use App\Enums\Payroll\PayrollRunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = [
        'company_id',
        'month',
        'year',
        'status',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'status' => PayrollRunStatus::class,
        'processed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function employeePayrolls(): HasMany
    {
        return $this->hasMany(EmployeePayroll::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class);
    }

    public function loanInstallments(): HasMany
    {
        return $this->hasMany(EmployeeLoanInstallment::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(PayrollRunHistory::class)->orderByDesc('version_no');
    }

    public function isLocked(): bool
    {
        return $this->status === PayrollRunStatus::LOCKED;
    }
}
