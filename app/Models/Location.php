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
        'name',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
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
        $address = $this->address;
        if ($this->city) {
            $address .= ', ' . $this->city;
        }
        if ($this->state) {
            $address .= ', ' . $this->state;
        }
        if ($this->country) {
            $address .= ', ' . $this->country;
        }
        if ($this->zip_code) {
            $address .= ' ' . $this->zip_code;
        }
        return $address;
    }
} 