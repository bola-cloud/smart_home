<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CityLite extends Model
{
    use HasFactory;

    protected $table = 'city_lites';
    protected $primaryKey = 'city_id';

    protected $fillable = ['city_id', 'region_id', 'name_ar', 'name_en'];

    // Define the relationships
    public function region()
    {
        return $this->belongsTo(RegionLite::class, 'region_id', 'region_id');
    }

    public function districts()
    {
        return $this->hasMany(DistrictLite::class, 'city_id', 'city_id');
    }
}
