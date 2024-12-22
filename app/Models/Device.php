<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = ['section_id', 'name', 'activation', 'last_updated',
    'device_type_id','serial','user_id','cancelled','ip','mac_address'];

    protected $casts = [
        'activation' => 'boolean',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(Component::class);
    }
    
    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class);
    }
}
