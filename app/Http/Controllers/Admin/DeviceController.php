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
        // Fetch query parameters
        $search = $request->input('search');
        $deviceTypeId = $request->input('device_type_id');
        $activation = $request->input('activation');

        // Start a query for devices with related models
        $query = Device::with(['deviceType', 'section']);

        // Apply search filter
        if (!empty($search)) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }

        // Apply device type filter
        if (!empty($deviceTypeId)) {
            $query->where('device_type_id', $deviceTypeId);
        }

        // Apply activation filter
        if (isset($activation)) {
            $query->where('activation', $activation);
        }

        // Paginate the results
        $devices = $query->paginate(30);

        // Fetch device types for filter dropdown
        $deviceTypes = DeviceType::all();

        // Return the view (for both initial load and AJAX)
        if ($request->ajax()) {
            return response()->json([
                'devices' => $devices->items(), // Return only the device data for the frontend
                'pagination' => (string) $devices->links('pagination::bootstrap-4'),
            ]);
        }

        // Render the main view
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
