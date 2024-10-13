<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    use HasFactory;

    protected $fillable = ['device_id', 'name', 'type'];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(Action::class);
    }
}
