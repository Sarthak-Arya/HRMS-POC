<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePolicyLeaveType extends Model
{
    protected $fillable = [
        'policy_id',
        'leave_type_id',
        'annual_quota',
        'carry_forward_limit',
        'encashable',
        'deduction_priority',
    ];

    protected $casts = [
        'annual_quota' => 'decimal:2',
        'carry_forward_limit' => 'decimal:2',
        'encashable' => 'boolean',
        'deduction_priority' => 'integer',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(AttendancePolicy::class, 'policy_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
