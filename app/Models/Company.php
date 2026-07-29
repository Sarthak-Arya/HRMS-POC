<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Department;
use App\Models\Location;
use App\Models\CompanySetting;

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
        'services_opted', 'is_esi', 'is_pf', 'b2b_firm_id',
    ];

    /**
     * Get the departments for the company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function departments()
    {
        return $this->hasMany(related: Department::class);
    }

    /**
     * Get the designations for the company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function designations()
    {
        return $this->hasMany(related: Designation::class);
    }

    /**
     * Get the locations for the company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function locations()
    {
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
     * Get the B2B firm that manages this company (if any).
     */
    public function b2bFirm(): BelongsTo
    {
        return $this->belongsTo(B2bFirm::class, 'b2b_firm_id');
    }

    /**
     * Direct B2C users scoped to this company.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'company_id');
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

    public function companySetting()
    {
        return $this->hasOne(CompanySetting::class);
    }
}
