<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Department;
use App\Models\Location;

/**
 * Model representing a company.
 * A company can have multiple departments, designations, locations, 
 * compensation components, and structures.
 */
class Company extends Model
{
    use HasFactory;

    /** @var string The table associated with the model */
    protected $table = 'company';

    /** @var array<int, string> The attributes that are mass assignable */
    protected $fillable = [
        'company_name', 'gst_number', 'company_address', 'zip_code', 'state', 'country',
        'esi_code', 'esi_contribution', 'esi_coverage_end_date', 'esi_coverage_start_date',
        'pf_code', 'pf_coverage_start_date', 'pf_coverage_end_date', 'pf_contribution',
        'services_opted', 'is_esi', 'is_pf', 'company_handled_by'
    ];

    /**
     * Get the departments for the company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function departments(){
        return $this->hasMany(related: Department::class);
    }
    
    /**
     * Get the designations for the company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function designations(){
        return $this->hasMany(related: Designation::class);
    }

    /**
     * Get the locations for the company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function locations(){
        return $this->hasMany(Location::class);
    }

    /**
     * Get the compensation components for the company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function compensationComponents()
    {
        return $this->hasMany(CompensationComponent::class);
    }

    /**
     * Get the compensation structures for the company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function compensationStructures()
    {
        return $this->hasMany(CompensationStructure::class);
    }

    /**
     * Get the user who handles the company.
     *
     * @return BelongsTo
     */
    public function handledBy(): BelongsTo{
        return $this->belongsTo(User::class);
    }

    /**
     * Get payroll runs for the company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payrollRuns()
    {
        return $this->hasMany(PayrollRun::class);
    }
}

