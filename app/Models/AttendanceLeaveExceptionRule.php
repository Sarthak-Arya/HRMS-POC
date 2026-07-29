<?php

namespace App\Models;

use App\Enums\Attendance\LeaveExceptionPayrollAction;
use App\Enums\Attendance\LeaveExceedAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLeaveExceptionRule extends Model
{
    protected $fillable = [
        'company_id',
        'policy_id',
        'leave_type_id',
        'max_days_per_month',
        'max_days_per_year',
        'on_exceed',
        'payroll_action',
        'is_active',
    ];

    protected $casts = [
        'max_days_per_month' => 'decimal:2',
        'max_days_per_year' => 'decimal:2',
        'on_exceed' => LeaveExceedAction::class,
        'payroll_action' => LeaveExceptionPayrollAction::class,
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(AttendancePolicy::class, 'policy_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
