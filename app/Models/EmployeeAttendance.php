<?php

namespace App\Models;

use App\Enums\Attendance\AttendanceRecordSource;
use App\Enums\Attendance\AttendanceStatus;
use App\Enums\Attendance\HalfDayStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAttendance extends Model
{
    protected $table = 'employee_attendance';

    protected $fillable = [
        'employee_id',
        'company_id',
        'attendance_date',
        'attendance_status',
        'leave_type_id',
        'first_half_status',
        'second_half_status',
        'worked_minutes',
        'overtime_minutes',
        'remarks',
        'source',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'attendance_status' => AttendanceStatus::class,
        'first_half_status' => HalfDayStatus::class,
        'second_half_status' => HalfDayStatus::class,
        'worked_minutes' => 'integer',
        'overtime_minutes' => 'integer',
        'source' => AttendanceRecordSource::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
