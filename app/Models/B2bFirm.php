<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class B2bFirm extends Model
{
    use HasFactory;

    protected $table = 'b2b_firms';

    protected $fillable = [
        'name',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'b2b_firm_id');
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'b2b_firm_id');
    }
}
