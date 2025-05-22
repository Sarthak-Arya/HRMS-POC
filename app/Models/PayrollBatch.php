<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'from_date',
        'to_date',
        'department_id',
        'designation_id',
        'status',
        'total_jobs',
        'processed_jobs',
        'failed_jobs'
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'failed_jobs' => 'array'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }
} 