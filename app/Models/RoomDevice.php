<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomDevice extends Model
{
    use HasFactory;
    protected $fillable = ['room_id', 'name', 'quantity', 'unit_price', 'total_price'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
