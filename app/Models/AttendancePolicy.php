<?php

namespace App\Models;

use App\Enums\Attendance\AttendanceMode;
use App\Enums\Attendance\LeaveBalancePriorityMode;
use App\Enums\Attendance\WeeklyOffRule;
use App\Enums\Attendance\WorkingDaysBasis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendancePolicy extends Model
{
    protected $fillable = [
        'company_id',
        'policy_name',
        'attendance_mode',
        'working_days_basis',
        'custom_working_days',
        'weekly_off_rule',
        'custom_weekly_off_days',
        'alternate_saturday_weeks',
        'grace_minutes',
        'min_half_day_minutes',
        'min_full_day_minutes',
        'allow_overtime',
        'allow_half_day',
        'allow_negative_leave_balance',
        'paid_holidays',
        'paid_weekly_offs',
        'require_attendance_before_holiday',
        'require_attendance_after_holiday',
        'auto_adjust_approved_leave',
        'sandwich_leave_enabled',
        'comp_off_enabled',
        'leave_balance_priority_mode',
        'auto_apply_week_offs',
        'auto_apply_holidays',
        'include_public_holidays',
        'include_regional_holidays',
        'include_emergency_holidays',
        'is_active',
    ];

    protected $casts = [
        'attendance_mode' => AttendanceMode::class,
        'working_days_basis' => WorkingDaysBasis::class,
        'weekly_off_rule' => WeeklyOffRule::class,
        'custom_working_days' => 'decimal:2',
        'custom_weekly_off_days' => 'array',
        'alternate_saturday_weeks' => 'array',
        'grace_minutes' => 'integer',
        'min_half_day_minutes' => 'integer',
        'min_full_day_minutes' => 'integer',
        'allow_overtime' => 'boolean',
        'allow_half_day' => 'boolean',
        'allow_negative_leave_balance' => 'boolean',
        'paid_holidays' => 'boolean',
        'paid_weekly_offs' => 'boolean',
        'require_attendance_before_holiday' => 'boolean',
        'require_attendance_after_holiday' => 'boolean',
        'auto_adjust_approved_leave' => 'boolean',
        'sandwich_leave_enabled' => 'boolean',
        'comp_off_enabled' => 'boolean',
        'leave_balance_priority_mode' => LeaveBalancePriorityMode::class,
        'auto_apply_week_offs' => 'boolean',
        'auto_apply_holidays' => 'boolean',
        'include_public_holidays' => 'boolean',
        'include_regional_holidays' => 'boolean',
        'include_emergency_holidays' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function policyLeaveTypes(): HasMany
    {
        return $this->hasMany(AttendancePolicyLeaveType::class, 'policy_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AttendancePolicyAssignment::class, 'policy_id');
    }

    public function summaries(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceSummary::class, 'policy_id');
    }
}
