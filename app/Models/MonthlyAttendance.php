<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyAttendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'employee_id',
        'company_id',
        'month',
        'year',
        'casual_leave',
        'earned_leave',
        'sick_leave',
        'holiday',
        'worked_days',
        'overtime_days',
        'overtime_hours',
        'esi_la',
        'total_days',
        'prev_leave_days',
        'prev_leave_amount',
        'shift_code',
        'ded_1',
        'ded_2',
        'ded_3',
        'deductions',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'casual_leave' => 'decimal:2',
        'earned_leave' => 'decimal:2',
        'sick_leave' => 'decimal:2',
        'holiday' => 'decimal:2',
        'worked_days' => 'decimal:2',
        'overtime_days' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'esi_la' => 'decimal:2',
        'total_days' => 'decimal:2',
        'prev_leave_days' => 'decimal:2',
        'prev_leave_amount' => 'decimal:2',
        'ded_1' => 'decimal:2',
        'ded_2' => 'decimal:2',
        'ded_3' => 'decimal:2',
        'deductions' => 'array',
    ];
}
