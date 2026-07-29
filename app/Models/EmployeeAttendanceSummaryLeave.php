<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAttendanceSummaryLeave extends Model
{
    protected $fillable = [
        'summary_id',
        'leave_type_id',
        'days',
    ];

    protected $casts = [
        'days' => 'decimal:2',
    ];

    public function summary(): BelongsTo
    {
        return $this->belongsTo(EmployeeAttendanceSummary::class, 'summary_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
