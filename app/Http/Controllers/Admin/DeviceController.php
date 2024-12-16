<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Section;
use App\Models\Component;

class DeviceController extends Controller
{
    /**
     * Display a listing of the devices.
     */
    public function index(Request $request)
    {
        // Fetch device types for filtering
        $deviceTypes = DeviceType::all();
    
        // Start the query for devices
        $query = Device::with(['deviceType', 'section']);
    
        // Apply search filter
        if ($request->has('search') && $request->search) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }
    
        // Apply device type filter
        if ($request->has('device_type_id') && $request->device_type_id) {
            $query->where('device_type_id', $request->device_type_id);
        }
    
        // Apply activation filter
        if ($request->has('activation') && $request->activation !== null) {
            $query->where('activation', $request->activation);
        }
    
        // Paginate the results
        $devices = $query->paginate(10);
    
        // Check if the request is AJAX (for dynamic filtering)
        if ($request->ajax()) {
            return view('admin.devices.partials.table', compact('devices'))->render();
        }
    
        // Return the full view
        return view('admin.devices.index', compact('devices', 'deviceTypes'));
    }
    
    /**
     * Show the form for creating a new device.
     */
    public function create()
    {
        $sections = Section::all(); // Get all sections
        $deviceTypes = DeviceType::with('channels')->get(); // Get all device types
        // dd($deviceTypes[15]->channels()->count());
        return view('admin.devices.create', compact('sections', 'deviceTypes'));
    }

    /**
     * Store a newly created device in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'device_type_id' => 'required|exists:device_types,id',
            'number_of_devices' => 'required|integer|min:1',
        ]);
    
        // Fetch device type and its channels
        $deviceType = DeviceType::findOrFail($request->input('device_type_id'));
        $channels = $deviceType->channels; // Assuming 'channels' is the relationship on the DeviceType model
    
        for ($i = 1; $i <= $request->input('number_of_devices'); $i++) {
            // Create the device
            $device = Device::create([
                'name' => $request->input('name'), // Append number to name
                'device_type_id' => $request->input('device_type_id'),
                'section_id' => $request->input('section_id'),
                'activation' => $request->input('activation', false),
            ]);
    
            // Create components based on channels
            foreach ($channels as $channel) {
                Component::create([
                    'device_id' => $device->id,
                    'name' => $channel->name, // Use the channel's name for the component
                    'order' => $channel->order, // Use the channel's name for the component
                    'channel_id' => $channel->id, // Reference the channel
                ]);
            }
        }
    
        return redirect()->route('devices.index')->with('success', 'Devices and components created successfully.');
    }    

    /**
     * Show the form for editing the specified device.
     */
    public function edit(Device $device)
    {
        $sections = Section::all(); // Get all sections
        $deviceTypes = DeviceType::all(); // Get all device types
        return view('admin.devices.edit', compact('device', 'sections', 'deviceTypes'));
    }

    /**
     * Update the specified device in storage.
     */
    public function update(Request $request, Device $device)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'device_type_id' => 'required',
            // 'section_id' => 'nullable|exists:sections,id',
        ]);

        $device->update($request->all());

        return redirect()->route('devices.index')->with('success', 'Device updated successfully.');
    }

    /**
     * Remove the specified device from storage.
     */
    public function destroy(Device $device)
    {
        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Device deleted successfully.');
    }
}
