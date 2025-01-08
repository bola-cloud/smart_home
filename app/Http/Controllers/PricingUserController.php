<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;

class PricingUserController extends Controller
{
    public function index()
    {
        $rooms = Room::with('devices')->get(); // Get all rooms with devices
        return view('villa-pricing', compact('rooms'));
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'room_quantities' => 'required|array',
            'room_quantities.*' => 'integer|min:0',
        ]);

        $total = 0;
        $details = [];
        foreach ($request->room_quantities as $roomId => $quantity) {
            $room = Room::with('devices')->find($roomId);
            if ($room && $quantity > 0) {
                $roomTotal = 0;
                foreach ($room->devices as $device) {
                    $deviceTotal = $device->total_price * $quantity;
                    $roomTotal += $deviceTotal;
                }

                $total += $roomTotal;
                $details[] = [
                    'room_name' => $room->name,
                    'quantity' => $quantity,
                    'total_cost' => $roomTotal,
                ];
            }
        }

        // Add internet access points (one per room, minimum 5)
        $internetPoints = max(array_sum($request->room_quantities), 5);
        $internetCost = $internetPoints * 500; // Example: 500 SAR per access point
        $total += $internetCost;

        return view('villa-result', compact('details', 'internetPoints', 'internetCost', 'total'));
    }
}
