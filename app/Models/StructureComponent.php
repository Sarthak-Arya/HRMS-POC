<?php

namespace App\Models;

use App\Enums\Compensation\CalculationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\StructureComponent
 *
 * @property int $id
 * @property int $structure_id
 * @property int $component_id
 * @property float|null $value
 * @property CalculationType|null $calculation_type
 * @property string|null $formula_expression
 * @property bool $is_mandatory
 * @property int $display_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CompensationStructure $structure
 * @property-read \App\Models\CompensationComponent $component
 */
class StructureComponent extends Model
{
    protected $fillable = [
        'structure_id',
        'component_id',
        'value',
        'calculation_type',
        'formula_expression',
        'is_mandatory',
        'display_order',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'calculation_type' => CalculationType::class,
        'is_mandatory' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the compensation structure that owns this component association.
     *
     * @return BelongsTo
     */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(CompensationStructure::class, 'structure_id');
    }

    /**
     * Get the compensation component for this association.
     *
     * @return BelongsTo
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(CompensationComponent::class, 'component_id');
    }

    /**
     * Resolve the calculation type, falling back to component default or FIXED.
     *
     * @return CalculationType
     */
    public function resolvedCalculationType(): CalculationType
    {
        return $this->calculation_type
            ?? $this->component?->default_calculation_type
            ?? CalculationType::FIXED;
    }
}
