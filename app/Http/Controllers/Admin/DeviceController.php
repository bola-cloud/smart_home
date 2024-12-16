<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Section;

class DeviceController extends Controller
{
    /**
     * Display a listing of the devices.
     */
    public function index()
    {
        $devices = Device::with('section')->get(); // Get all devices with their related sections
        return view('admin.devices.index', compact('devices'));
    }

    /**
     * Show the form for creating a new device.
     */
    public function create()
    {
        $sections = Section::all(); // Get all sections
        $deviceTypes = DeviceType::all(); // Get all device types
        return view('admin.devices.create', compact('sections', 'deviceTypes'));
    }

    /**
     * Store a newly created device in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'device_type_id' => 'required',
            // 'section_id' => 'nullable|exists:sections,id',
            'number_of_devices' => 'required|integer|min:1', // Validate the number of devices
        ]);
    
        // Number of devices to create
        $numberOfDevices = $request->input('number_of_devices');
    
        for ($i = 1; $i <= $numberOfDevices; $i++) {
            // Create the device with incremented name
            $device = Device::create([
                'name' => $request->input('name') . ' ' . $i, // Append the number to the name
                'device_type_id' => $request->input('device_type_id'),
                'section_id' => $request->input('section_id'),
                'activation' => $request->input('activation', false),
            ]);
    
            // // Generate a unique serial number (based on the device ID)
            // $device->update([
            //     'serial' => $device->id . '-' . rand(1000000, 9999999), // Use device ID + random number for serial
            // ]);
        }
    
        return redirect()->route('devices.index')->with('success', 'Devices created successfully.');
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
