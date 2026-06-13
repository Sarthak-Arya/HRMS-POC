<?php

namespace App\Models;

use App\Enums\Compensation\CompensationScopeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\CompensationStructureAssignment
 *
 * @property int $id
 * @property int $company_id
 * @property CompensationScopeType $scope_type
 * @property int $scope_id
 * @property int $structure_id
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_to
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\CompensationStructure $structure
 */
class CompensationStructureAssignment extends Model
{
    protected $fillable = [
        'company_id',
        'scope_type',
        'scope_id',
        'structure_id',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'scope_type' => CompensationScopeType::class,
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /**
     * Get the company that owns the assignment.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the compensation structure for this assignment.
     *
     * @return BelongsTo
     */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(CompensationStructure::class, 'structure_id');
    }
}
