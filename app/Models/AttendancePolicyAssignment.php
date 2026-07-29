<?php

namespace App\Models;

use App\Enums\Attendance\AttendanceScopeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePolicyAssignment extends Model
{
    protected $fillable = [
        'company_id',
        'scope_type',
        'scope_id',
        'policy_id',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'scope_type' => AttendanceScopeType::class,
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(AttendancePolicy::class, 'policy_id');
    }
}
