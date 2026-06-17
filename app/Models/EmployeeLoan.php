<?php

namespace App\Models;

use App\Enums\Payroll\EmployeeLoanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeLoan extends Model
{
    protected $fillable = [
        'employee_id',
        'loan_name',
        'principal_amount',
        'emi_amount',
        'start_month',
        'start_year',
        'remaining_amount',
        'status',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'emi_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'start_month' => 'integer',
        'start_year' => 'integer',
        'status' => EmployeeLoanStatus::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(EmployeeLoanInstallment::class, 'loan_id');
    }
}
