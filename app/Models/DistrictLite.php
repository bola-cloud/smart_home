<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistrictLite extends Model
{
    use HasFactory;

    protected $table = 'districts_lite';
    protected $primaryKey = 'district_id'; // Setting primary key if not "id"

    protected $fillable = ['district_id', 'city_id', 'region_id', 'name_ar', 'name_en', 'shipping'];

    // Define the relationships
    public function city()
    {
        return $this->belongsTo(CityLite::class, 'city_id', 'city_id');
    }

    public function region()
    {
        return $this->belongsTo(RegionLite::class, 'region_id', 'region_id');
    }
}
