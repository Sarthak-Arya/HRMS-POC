<?php

namespace App\Models;

use App\Enums\Attendance\AttendanceLogSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAttendanceLog extends Model
{
    protected $fillable = [
        'employee_id',
        'company_id',
        'attendance_date',
        'clock_in',
        'clock_out',
        'worked_minutes',
        'late_minutes',
        'early_exit_minutes',
        'overtime_minutes',
        'source',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'worked_minutes' => 'integer',
        'late_minutes' => 'integer',
        'early_exit_minutes' => 'integer',
        'overtime_minutes' => 'integer',
        'source' => AttendanceLogSource::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
