<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $table = 'locations';

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

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function compensationStructures()
    {
        return $this->hasMany(CompensationStructure::class, 'applies_to_id')
                    ->where('applies_to_type', 'location');
    }

    // Helper method to get full address
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

    public function getNameAttribute(): string
    {
        return (string) $this->location_name;
    }
}
