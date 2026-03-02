<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $casts = [
        'dob' => 'date',
        'doj' => 'date',
        'dol' => 'date',
    ];

    protected $table = 'employees';

    protected $fillable = [
        'company_id',
        'employee_code',
        'employee_name',
        'gender',
        'father_name',
        'location_id',
        'dob',
        'doj',
        'dol',
        'present_address_line1',
        'present_address_line2',
        'present_city',
        'present_state',
        'present_pincode',
        'present_country',
        'permanent_address_line1',
        'permanent_address_line2',
        'permanent_city',
        'permanent_state',
        'permanent_pincode',
        'permanent_country',
        'basic_salary',
        'hra',
        'conveyance',
        'cca',
        'da',
        'pf_no',
        'esi_no',
        'department_id',
        'designation_id',
        'pay_mode',
        'pf_mode',
        'bank_name',
        'bank_account_no',
        'bank_ifsc_code',
        'bank_account_type',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function compensationStructure()
    {
        return $this->belongsToMany(
            CompensationStructure::class,
            'employee_compensation_structure',
            'employee_id',
            'structure_id'
        )->withPivot(['override', 'effective_from', 'effective_to'])->withTimestamps();
    }

    public function getFullNameAttribute()
    {
        return $this->employee_name;
    }

    public function payrollHeaders()
    {
        return $this->hasMany(PayrollHeader::class);
    }

    public function earnings()
    {
        return $this->hasMany(Earning::class);
    }

    public function deductions()
    {
        return $this->hasMany(Deduction::class);
    }
}
