<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollHeader extends Model
{
    use HasFactory;

    protected $table = 'payroll_header';

    protected $guarded = [];

    protected $casts = [
        'total_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'pay_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class, 'payroll_id');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(Deduction::class, 'payroll_id');
    }

    public function payrollEarnings(): HasMany
    {
        return $this->hasMany(PayrollEarning::class, 'payroll_id');
    }

    public function payrollDeductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class, 'payroll_id');
    }
}


