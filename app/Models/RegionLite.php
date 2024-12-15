<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegionLite extends Model
{
    use HasFactory;

    protected $table = 'regions_lite';
    protected $primaryKey = 'region_id';
    public $timestamps = false;

    protected $fillable = ['region_id', 'capital_city_id', 'code', 'name_ar', 'name_en', 'population'];

    // Define the relationships
    public function cities()
    {
        return $this->hasMany(CityLite::class, 'region_id', 'region_id');
    }

    public function capitalCity()
    {
        return $this->belongsTo(CityLite::class, 'capital_city_id', 'city_id');
    }
}
