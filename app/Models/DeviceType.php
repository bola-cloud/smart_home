<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class,'device_type_id');
    }
}
