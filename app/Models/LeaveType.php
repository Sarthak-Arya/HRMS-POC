<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $table = 'leave_types';

    protected $fillable = [
        'company_id',
        'location_id',
        'leave_name',
        'leave_code',
        'is_paid',
        'description',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
    ];
}

