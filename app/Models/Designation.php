<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'designation_name',
    ];

    public function company()
    {    
        return $this->belongsTo(Company::class);
    }


    public function employees()
    {
        return $this->hasMany(Employee::class, 'designation_id');
    }
}
