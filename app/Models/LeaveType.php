<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'is_paid',
        'annual_quota',
        'carry_forward',
        'encashable',
        'is_active',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'annual_quota' => 'decimal:2',
        'carry_forward' => 'boolean',
        'encashable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function policyLeaveTypes(): HasMany
    {
        return $this->hasMany(AttendancePolicyLeaveType::class);
    }

    public function summaryLeaves(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceSummaryLeave::class);
    }

    public function dailyRecords(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }
}
