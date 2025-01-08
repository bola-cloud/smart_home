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
        $totalPartitions = 0; // Count the total partitions (rooms)
    
        foreach ($request->room_types as $index => $roomId) {
            $room = Room::with('devices')->find($roomId);
            if ($room) {
                $roomQuantity = $request->room_quantities[$index]; // Number of this room type
                $roomTotal = 0;
                $devices = [];
    
                foreach ($room->devices as $device) {
                    $deviceCost = $device->quantity * $device->unit_price * $roomQuantity;
                    $roomTotal += $deviceCost;
                    $devices[] = [
                        'name' => $device->name,
                        'quantity' => $device->quantity,
                        'unit_price' => $device->unit_price,
                        'total_price' => $deviceCost,
                    ];
                }
    
                $totalCost += $roomTotal;
                $totalPartitions += $roomQuantity; // Add to total partitions
                $selectedRooms[] = [
                    'name' => $room->name,
                    'quantity' => $roomQuantity,
                    'devices' => $devices,
                    'total_cost' => $roomTotal,
                ];
            }
        }
    
        // Calculate internet access points (minimum 5, one per partition)
        $accessPoints = max($totalPartitions, 5); // At least 5 access points
        $accessPointsCost = $accessPoints * 500; // Assume 500 is the cost per access point
        $totalCost += $accessPointsCost;
    
        return view('villa-result', compact('selectedRooms', 'accessPoints', 'accessPointsCost', 'totalCost', 'totalPartitions'));
    }
    
}
