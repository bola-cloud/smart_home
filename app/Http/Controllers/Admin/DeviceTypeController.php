<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeviceType;
use App\Models\Channel;

class DeviceTypeController extends Controller
{
    public function index()
    {
        $deviceTypes = DeviceType::all();
        return view('admin.device_types.index', compact('deviceTypes'));
    }

    public function create()
    {
        return view('admin.device_types.create_with_channels');
    }

    public function store(Request $request)
    {
        $request->validate([
            'device_type_name' => 'required|string|max:255',
            'channels.*.name' => 'required|string|max:255',
        ]);
    
        // Create the device type
        $deviceType = DeviceType::create([
            'name' => $request->input('device_type_name'),
        ]);
    
        // Channels creation without requiring manual order input
        $channels = $request->input('channels', []);
        foreach ($channels as $index => $channelData) {
            Channel::create([
                'name' => $channelData['name'],
                'device_type_id' => $deviceType->id,
                'order' => $index , // Automatically assign order based on input sequence
            ]);
        }
    
        return redirect()->route('device_types.index')->with('success', __('lang.device_type_created'));
    }

    public function edit(DeviceType $deviceType)
    {
        return view('admin.device_types.edit', compact('deviceType'));
    }

    // Update the device type and its channels
    public function update(Request $request, DeviceType $deviceType)
    {
        $request->validate([
            'device_type_name' => 'required|string|max:255',
            'channels.*.name' => 'required|string|max:255',
        ]);
    
        // Update the device type name
        $deviceType->update([
            'name' => $request->input('device_type_name'),
        ]);
    
        // Delete existing channels to update with the new ones
        $deviceType->channels()->delete();
    
        // Recreate channels with updated order
        $channels = $request->input('channels', []);
        foreach ($channels as $index => $channelData) {
            Channel::create([
                'name' => $channelData['name'],
                'device_type_id' => $deviceType->id,
                'order' => $index , // Automatically assign order
            ]);
        }
    
        return redirect()->route('device_types.index')->with('success', __('lang.device_type_updated'));
    }        

    public function destroy(DeviceType $deviceType)
    {
        $deviceType->delete();
        return redirect()->route('device_types.index')->with('success', __('lang.device_type_deleted'));
    }
    
}