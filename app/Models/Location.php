<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model representing a company location.
 * Stores address and contact information for a specific site or branch.
 */
class Location extends Model
{
    use HasFactory;

    /** @var string The table associated with the model */
    protected $table = 'locations';

    /** @var array<int, string> The attributes that are mass assignable */
    protected $fillable = [
        'company_id',
        'location_name',
        'location_code',
        'location_address',
        'location_city',
        'location_state',
        'location_pincode',
        'location_country',
        'location_phone',
        'location_email',
    ];

    /**
     * Get the company that owns the location.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employees assigned to this location.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Helper method to get the full formatted address.
     *
     * @return string Full address as a string.
     */
    public function getFullAddressAttribute()
    {
        $address = (string) $this->location_address;
        if ($this->location_city) {
            $address .= ', ' . $this->location_city;
        }
        if ($this->location_state) {
            $address .= ', ' . $this->location_state;
        }
        if ($this->location_country) {
            $address .= ', ' . $this->location_country;
        }
        if ($this->location_pincode) {
            $address .= ' ' . $this->location_pincode;
        }
        return $address;
    }

    /**
     * Get the location name as an attribute.
     *
     * @return string Location name.
     */
    public function getNameAttribute(): string
    {
        return (string) $this->location_name;
    }
}

