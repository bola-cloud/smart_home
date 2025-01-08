<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;

class PricingUserController extends Controller
{
    public function index()
    {
        $rooms = Room::all(); // Get all room types
        return view('villa-pricing', compact('rooms'));
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'number_of_rooms' => 'required|integer|min:1',
            'room_types' => 'required|array',
            'room_types.*' => 'exists:rooms,id',
        ]);

        $selectedRooms = [];
        $totalCost = 0;

        foreach ($request->room_types as $roomId) {
            $room = Room::with('devices')->find($roomId);
            if ($room) {
                $roomTotal = 0;
                $devices = [];
                foreach ($room->devices as $device) {
                    $deviceCost = $device->quantity * $device->unit_price;
                    $roomTotal += $deviceCost;
                    $devices[] = [
                        'name' => $device->name,
                        'quantity' => $device->quantity,
                        'unit_price' => $device->unit_price,
                        'total_price' => $deviceCost,
                    ];
                }
                $totalCost += $roomTotal;
                $selectedRooms[] = [
                    'name' => $room->name,
                    'devices' => $devices,
                    'total_cost' => $roomTotal,
                ];
            }
        }

        // Add internet access points (minimum 5, one per room)
        $numberOfRooms = count($request->room_types);
        $accessPoints = max($numberOfRooms, 5);
        $accessPointsCost = $accessPoints * 500;
        $totalCost += $accessPointsCost;

        return view('villa-result', compact('selectedRooms', 'accessPoints', 'accessPointsCost', 'totalCost'));
    }
}
