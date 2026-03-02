<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Department;
use App\Models\Location;

class Company extends Model
{
    use HasFactory;

    protected $table = 'company';

    
    protected $fillable = [
        'company_name', 'gst_number', 'company_address', 'zip_code', 'state', 'country',
        'esi_code', 'esi_contribution', 'esi_coverage_end_date', 'esi_coverage_start_date',
        'pf_code', 'pf_coverage_start_date', 'pf_coverage_end_date', 'pf_contribution',
        'services_opted', 'is_esi', 'is_pf'
    ];

    public function departments(){
        return $this->hasMany(related: Department::class);
    }
    
    public function designations(){
        return $this->hasMany(related: Designation::class);
    }

    public function locations(){
        return $this->hasMany(Location::class);
    }

    public function handledBy(): BelongsTo{
        return $this->belongsTo(User::class);
    }
}
