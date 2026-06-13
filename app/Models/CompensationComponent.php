<?php

namespace App\Models;

use App\Enums\Compensation\CalculationType;
use App\Enums\Compensation\ComponentType;
use App\Enums\Compensation\StatutoryComponent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\CompensationComponent
 *
 * @property int $id
 * @property int $company_id
 * @property string $component_name
 * @property ComponentType $component_type
 * @property CalculationType $default_calculation_type
 * @property float $default_value
 * @property StatutoryComponent|null $statutory_component
 * @property bool $is_taxable
 * @property bool $is_active
 * @property int $display_order
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\StructureComponent[] $structureComponents
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\CompensationOverride[] $overrides
 * @property-read \App\Models\User $createdBy
 */
class CompensationComponent extends Model
{
    protected $fillable = [
        'company_id',
        'component_name',
        'component_type',
        'default_calculation_type',
        'default_value',
        'statutory_component',
        'is_taxable',
        'is_active',
        'display_order',
        'created_by',
    ];

    protected $casts = [
        'component_type' => ComponentType::class,
        'default_calculation_type' => CalculationType::class,
        'statutory_component' => StatutoryComponent::class,
        'default_value' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the company that owns the component.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the structure components for this component.
     *
     * @return HasMany
     */
    public function structureComponents(): HasMany
    {
        return $this->hasMany(StructureComponent::class, 'component_id');
    }

    /**
     * Get the overrides for this component.
     *
     * @return HasMany
     */
    public function overrides(): HasMany
    {
        return $this->hasMany(CompensationOverride::class, 'component_id');
    }

    /**
     * Get the user who created the component.
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
