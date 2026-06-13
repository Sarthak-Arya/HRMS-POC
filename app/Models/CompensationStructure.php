<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\CompensationStructure
 *
 * @property int $id
 * @property int $company_id
 * @property string $structure_name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_to
 * @property bool $is_active
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\StructureComponent[] $structureComponents
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\CompensationStructureAssignment[] $assignments
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\EmployeeCompensationHistory[] $compensationHistories
 */
class CompensationStructure extends Model
{
    protected $fillable = [
        'company_id',
        'structure_name',
        'description',
        'effective_from',
        'effective_to',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get the company that owns the structure.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the components for this structure.
     *
     * @return HasMany
     */
    public function structureComponents(): HasMany
    {
        return $this->hasMany(StructureComponent::class, 'structure_id')->orderBy('display_order');
    }

    /**
     * Get the assignments for this structure.
     *
     * @return HasMany
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(CompensationStructureAssignment::class, 'structure_id');
    }

    /**
     * Get the compensation histories for this structure.
     *
     * @return HasMany
     */
    public function compensationHistories(): HasMany
    {
        return $this->hasMany(EmployeeCompensationHistory::class, 'structure_id');
    }
}
