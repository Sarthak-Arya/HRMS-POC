<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompensationDetails extends Model
{
    protected $table = "compensation_details";
    protected $fillable = [
        'compensation_id',
        'compensation_type',
        'percentage'
    ];

    
    public function compensation()
    {
        return $this->belongsTo(CompensationStructure::class);
    }
    use HasFactory;
}
