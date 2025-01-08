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
            'room_types' => 'required|array|min:1', // Ensure room_types is an array with at least one item
            'room_types.*' => 'required|exists:rooms,id', // Validate each room type ID exists in the rooms table
            'room_quantities' => 'required|array|min:1', // Ensure room_quantities is an array with at least one item
            'room_quantities.*' => 'required|integer|min:1', // Each room quantity must be an integer >= 1
        ], [
            'room_types.required' => 'يجب اختيار نوع الغرفة.', // Custom validation message for missing room types
            'room_types.*.exists' => 'نوع الغرفة المحدد غير موجود.', // Custom message for invalid room type
            'room_quantities.required' => 'يجب إدخال عدد الغرف.', // Custom message for missing quantities
            'room_quantities.*.integer' => 'عدد الغرف يجب أن يكون عددًا صحيحًا.', // Custom message for non-integer quantities
            'room_quantities.*.min' => 'عدد الغرف يجب أن يكون على الأقل 1.', // Custom message for invalid quantity
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
