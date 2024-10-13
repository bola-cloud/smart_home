<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = ['section_id', 'name', 'type', 'status', 'last_updated'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(Component::class);
    }
}
