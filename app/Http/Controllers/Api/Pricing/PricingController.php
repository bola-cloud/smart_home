<?php

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;

class PricingController extends Controller
{
    public function getRooms()
    {
        $rooms = Room::all(['id', 'name']);
        return response()->json($rooms);
    }
    
}
