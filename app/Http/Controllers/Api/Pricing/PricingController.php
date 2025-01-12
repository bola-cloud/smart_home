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

    public function calculate(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'room_types' => 'required|array|min:1',
            'room_types.*' => 'required|exists:rooms,id',
            'room_quantities' => 'required|array|min:1',
            'room_quantities.*' => 'required|integer|min:1',
        ], [
            'room_types.required' => 'يجب اختيار نوع الغرفة.',
            'room_types.*.exists' => 'نوع الغرفة المحدد غير موجود.',
            'room_quantities.required' => 'يجب إدخال عدد الغرف.',
            'room_quantities.*.integer' => 'عدد الغرف يجب أن يكون عددًا صحيحًا.',
            'room_quantities.*.min' => 'عدد الغرف يجب أن يكون على الأقل 1.',
        ]);

        $selectedRooms = [];
        $totalCost = 0;
        $totalPartitions = 0; // Total number of rooms/partitions

        foreach ($validated['room_types'] as $index => $roomId) {
            $room = Room::with('devices')->find($roomId);
            if ($room) {
                $roomQuantity = $validated['room_quantities'][$index];
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
                $totalPartitions += $roomQuantity;
                $selectedRooms[] = [
                    'name' => $room->name,
                    'quantity' => $roomQuantity,
                    'devices' => $devices,
                    'total_cost' => $roomTotal,
                ];
            }
        }

        // Calculate internet access points
        $accessPoints = max($totalPartitions, 5); // Minimum 5 access points
        $accessPointsCost = $accessPoints * 500; // Fixed cost per access point
        $totalCost += $accessPointsCost;

        // Return JSON response
        return response()->json([
            'success' => true,
            'data' => [
                'selected_rooms' => $selectedRooms,
                'total_partitions' => $totalPartitions,
                'access_points' => $accessPoints,
                'access_points_cost' => $accessPointsCost,
                'total_cost' => $totalCost,
            ]
        ]);
    }

}
