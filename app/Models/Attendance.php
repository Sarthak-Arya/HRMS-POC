<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'from_date',
        'to_date',
        'days',
        'casual_leave',
        'earned_leave',
        'maternity_leave',
        'earnings',
        'deductions'
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'earnings' => 'array',
        'deductions' => 'array'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
} 