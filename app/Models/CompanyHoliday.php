<?php

namespace App\Models;

use App\Enums\Attendance\HolidaySourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyHoliday extends Model
{
    protected $fillable = [
        'company_id',
        'holiday_date',
        'name',
        'source_type',
        'location_id',
        'region_code',
        'is_paid',
        'is_active',
        'is_recurring',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'source_type' => HolidaySourceType::class,
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
