<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Governorate extends Model
{
    use HasFactory;

    protected $fillable = ['governorate_name_ar', 'governorate_name_en'];

    // Define the one-to-many relationship with cities
    public function cities()
    {
        return $this->hasMany(City::class);
    }
}
