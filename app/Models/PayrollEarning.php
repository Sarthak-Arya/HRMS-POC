<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollEarning extends Model
{
    use HasFactory;

    protected $table = 'payroll_earnings';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(PayrollHeader::class, 'payroll_id');
    }

    public function earningType(): BelongsTo
    {
        return $this->belongsTo(Earning::class, 'earning_type_id');
    }
}


