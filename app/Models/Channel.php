<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = ['name','order','device_type_id'];

    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class,'device_type_id');
    }

}
