<?php

namespace App\Http\Controllers\Admin\Pricing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\RoomDevice;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::all();
        return view('admin.pricing.rooms.index', compact('rooms'));
    }

    public function create()
    {
        return view('admin.pricing.rooms.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        Room::create(['name' => $request->name]);
        return redirect()->route('pricing.rooms.index')->with('success', 'Room added successfully.');
    }

    public function edit($id)
    {
        $room = Room::findOrFail($id);
        return view('admin.pricing.rooms.edit', compact('room'));
    }

    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $room = Room::findOrFail($id);
        $room->update(['name' => $request->name]);
        return redirect()->route('pricing.rooms.index')->with('success', 'Room updated successfully.');
    }

    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        $room->delete();
        return redirect()->route('pricing.rooms.index')->with('success', 'Room deleted successfully.');
    }
}
