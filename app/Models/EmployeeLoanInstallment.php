<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLoanInstallment extends Model
{
    protected $fillable = [
        'loan_id',
        'payroll_run_id',
        'installment_no',
        'amount',
        'deducted_on',
    ];

    protected $casts = [
        'installment_no' => 'integer',
        'amount' => 'decimal:2',
        'deducted_on' => 'date',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(EmployeeLoan::class, 'loan_id');
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }
}
