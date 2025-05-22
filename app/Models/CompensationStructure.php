<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompensationStructure extends Model
{
    protected $table = "compensations";
    protected $fillable = [
        'company_id',
        'designation_id',
        'department_id',
        'name',
        'structure'
    ];

    protected $casts = [
        'structure' => 'array',
    ];

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
} 