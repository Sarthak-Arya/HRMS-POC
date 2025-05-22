<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'from_date',
        'to_date',
        'salary_json'
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'salary_json' => 'array'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
} 