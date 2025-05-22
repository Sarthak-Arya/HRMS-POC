<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;
    protected $casts = [
        'employee_joining_date' => 'date',
    ];
    
    protected $table = 'employees';
    
    protected $fillable = [
        'company_id',
        'employee_first_name',
        'employee_middle_name',
        'employee_last_name',
        'employee_father_name',
        'employee_gender',
        'employee_dob',
        'employee_company_code',
        'employee_joining_date',
        'employee_leaving_date',
        'employee_esi_no',
        'employee_pf_no',
        'designation_id',
        'department_id',
        'employee_age'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }
}
