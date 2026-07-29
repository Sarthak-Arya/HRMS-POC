<?php

namespace App\Models;

use App\Enums\Attendance\AttendanceEntrySource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeAttendanceSummary extends Model
{
    protected $table = 'employee_attendance_summaries';

    protected $fillable = [
        'employee_id',
        'company_id',
        'month',
        'year',
        'policy_id',
        'entry_source',
        'calendar_days',
        'working_days',
        'present_days',
        'half_days',
        'paid_leave_days',
        'lop_days',
        'weekly_off_days',
        'holiday_days',
        'overtime_hours',
        'worked_days',
        'overtime_days',
        'total_days',
        'prev_leave_days',
        'prev_leave_amount',
        'esi_la',
        'shift_code',
        'ded_1',
        'ded_2',
        'ded_3',
        'deductions',
        'locked_at',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'entry_source' => AttendanceEntrySource::class,
        'calendar_days' => 'decimal:2',
        'working_days' => 'decimal:2',
        'present_days' => 'decimal:2',
        'half_days' => 'decimal:2',
        'paid_leave_days' => 'decimal:2',
        'lop_days' => 'decimal:2',
        'weekly_off_days' => 'decimal:2',
        'holiday_days' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'worked_days' => 'decimal:2',
        'overtime_days' => 'decimal:2',
        'total_days' => 'decimal:2',
        'prev_leave_days' => 'decimal:2',
        'prev_leave_amount' => 'decimal:2',
        'esi_la' => 'decimal:2',
        'ded_1' => 'decimal:2',
        'ded_2' => 'decimal:2',
        'ded_3' => 'decimal:2',
        'deductions' => 'array',
        'locked_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(AttendancePolicy::class, 'policy_id');
    }

    public function leaveBreakdown(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceSummaryLeave::class, 'summary_id');
    }

    public function employeePayrolls(): HasMany
    {
        return $this->hasMany(EmployeePayroll::class, 'attendance_summary_id');
    }

    public function leaveDaysForCode(string $code): float
    {
        if (! $this->relationLoaded('leaveBreakdown')) {
            $this->load('leaveBreakdown.leaveType');
        }

        $row = $this->leaveBreakdown->first(
            fn (EmployeeAttendanceSummaryLeave $leave) => $leave->leaveType?->code === $code
        );

        return (float) ($row?->days ?? 0);
    }

    /**
     * @param  array<string, float>  $leaveDaysByCode
     */
    public function syncLeaveBreakdown(array $leaveDaysByCode): void
    {
        $leaveTypes = LeaveType::query()
            ->where('company_id', $this->company_id)
            ->whereIn('code', array_keys($leaveDaysByCode))
            ->get()
            ->keyBy('code');

        foreach ($leaveDaysByCode as $code => $days) {
            $leaveType = $leaveTypes->get($code);
            if (! $leaveType) {
                continue;
            }

            if ($days <= 0) {
                $this->leaveBreakdown()->where('leave_type_id', $leaveType->id)->delete();

                continue;
            }

            $this->leaveBreakdown()->updateOrCreate(
                ['leave_type_id' => $leaveType->id],
                ['days' => $days]
            );
        }
    }
}
