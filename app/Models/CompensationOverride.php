<?php

namespace App\Models;

use App\Enums\Compensation\CalculationType;
use App\Enums\Compensation\CompensationScopeType;
use App\Enums\Compensation\OverrideType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\CompensationOverride
 *
 * @property int $id
 * @property int $company_id
 * @property CompensationScopeType $scope_type
 * @property int $scope_id
 * @property int $component_id
 * @property OverrideType $override_type
 * @property float|null $value
 * @property CalculationType|null $calculation_type
 * @property string|null $formula_expression
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_to
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\CompensationComponent $component
 * @property-read \App\Models\User $createdBy
 */
class CompensationOverride extends Model
{
    protected $fillable = [
        'company_id',
        'scope_type',
        'scope_id',
        'component_id',
        'override_type',
        'value',
        'calculation_type',
        'formula_expression',
        'effective_from',
        'effective_to',
        'created_by',
    ];

    protected $casts = [
        'scope_type' => CompensationScopeType::class,
        'override_type' => OverrideType::class,
        'calculation_type' => CalculationType::class,
        'value' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /**
     * Get the company that owns the override.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the compensation component for this override.
     *
     * @return BelongsTo
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(CompensationComponent::class, 'component_id');
    }

    /**
     * Get the user who created the override.
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
