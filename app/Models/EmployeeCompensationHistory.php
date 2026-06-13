<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\EmployeeCompensationHistory
 *
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property int $structure_id
 * @property float $annual_ctc
 * @property float $monthly_gross
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_to
 * @property string|null $revision_reason
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Employee $employee
 * @property-read \App\Models\CompensationStructure $structure
 * @property-read \App\Models\User|null $approvedBy
 */
class EmployeeCompensationHistory extends Model
{
    protected $table = 'employee_compensation_history';

    protected $fillable = [
        'company_id',
        'employee_id',
        'structure_id',
        'annual_ctc',
        'monthly_gross',
        'effective_from',
        'effective_to',
        'revision_reason',
        'approved_by',
    ];

    protected $casts = [
        'annual_ctc' => 'decimal:2',
        'monthly_gross' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /**
     * Get the company that owns the compensation history.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee for this compensation history.
     *
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the compensation structure for this history entry.
     *
     * @return BelongsTo
     */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(CompensationStructure::class, 'structure_id');
    }

    /**
     * Get the user who approved this revision.
     *
     * @return BelongsTo
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if this history entry is active on a given date.
     *
     * @param \Carbon\Carbon $date
     * @return bool
     */
    public function isActiveOn(\Carbon\Carbon $date): bool
    {
        if ($this->effective_from->gt($date)) {
            return false;
        }

        return $this->effective_to === null || $this->effective_to->gte($date);
    }
}
