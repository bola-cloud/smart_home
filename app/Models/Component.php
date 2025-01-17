<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Component extends Model
{
    use HasFactory;

    protected $fillable = ['device_id', 'name', 'type','order','image_id','file_path','manual'];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(Action::class);
    }
}
