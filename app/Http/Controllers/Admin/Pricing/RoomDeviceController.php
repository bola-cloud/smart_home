<?php

namespace App\Http\Controllers\Admin\Pricing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\RoomDevice;

class RoomDeviceController extends Controller
{
    public function index($room_id)
    {
        $room = Room::findOrFail($room_id);
        $devices = $room->devices;
        return view('admin.pricing.devices.index', compact('room', 'devices'));
    }

    public function create($room_id)
    {
        $room = Room::findOrFail($room_id);
        return view('admin.pricing.devices.create', compact('room'));
    }

    public function store(Request $request, $room_id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $total_price = $request->quantity * $request->unit_price;

        RoomDevice::create([
            'room_id' => $room_id,
            'name' => $request->name,
            'quantity' => $request->quantity,
            'unit_price' => $request->unit_price,
            'total_price' => $total_price,
        ]);

        return redirect()->route('pricing.devices.index', $room_id)->with('success', 'Device added successfully.');
    }

    public function edit($id)
    {
        $device = RoomDevice::findOrFail($id);
        return view('admin.pricing.devices.edit', compact('device'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $device = RoomDevice::findOrFail($id);

        $device->update([
            'name' => $request->name,
            'quantity' => $request->quantity,
            'unit_price' => $request->unit_price,
            'total_price' => $request->quantity * $request->unit_price,
        ]);

        return redirect()->route('pricing.devices.index', $device->room_id)->with('success', 'Device updated successfully.');
    }

    public function destroy($id)
    {
        $device = RoomDevice::findOrFail($id);
        $room_id = $device->room_id;
        $device->delete();
        return redirect()->route('pricing.devices.index', $room_id)->with('success', 'Device deleted successfully.');
    }
}
