<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    use HasFactory;

    protected $fillable = ['device_id', 'component_id', 'action_type', 'status', 'json_data', 'timestamp'];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }
}
