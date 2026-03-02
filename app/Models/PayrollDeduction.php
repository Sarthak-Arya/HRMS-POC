<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDeduction extends Model
{
    use HasFactory;

    protected $table = 'payroll_deductions';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(PayrollHeader::class, 'payroll_id');
    }

    public function deductionType(): BelongsTo
    {
        return $this->belongsTo(Deduction::class, 'deduction_type_id');
    }
}


