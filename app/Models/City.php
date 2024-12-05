<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = ['city_name_ar', 'city_name_en', 'governorate_id', 'shipping'];

    // Define the inverse of the relationship
    public function governorate()
    {
        return $this->belongsTo(Governorate::class);
    }
}
