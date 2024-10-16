<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Channel;
use App\Models\Component;
use App\Models\Device;
use App\Models\DeviceType;

class ComponentController extends Controller
{
    public function storeForDevice(Request $request, Device $device)
    {
        $request->validate([
            'components.*.name' => 'required|string|max:255',
        ]);
    
        // Loop through each component and create it with the same order as the corresponding channel
        foreach ($request->input('components') as $channelId => $componentData) {
            Component::create([
                'name' => $componentData['name'],
                'device_id' => $device->id,
                'order' => $componentData['order'], // Use the channel's order for the component
                'serial' => $device->id . '-' . rand(100000, 999999), // Generate unique serial for each component
            ]);
        }
    
        return redirect()->route('devices.index')->with('success', __('lang.components_added'));
    }

    public function updateOrderAndEdit(Request $request, $deviceId)
    {
        $componentsData = $request->input('components');
        
        foreach ($componentsData as $componentData) {
            $component = Component::find($componentData['id']);
            if ($component) {
                $component->update([
                    'name' => $componentData['name'],
                    'order' => $componentData['order'],
                ]);
            }
        }
    
        return response()->json(['success' => true]);  // Ensure JSON response
    }    

    public function update(Request $request, Component $component)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'order' => 'required|integer',
        ]);

        $component->update($request->all());

        return redirect()->route('components.index_for_device', $component->device_id)
                        ->with('success', __('Component updated successfully.'));
    }

    public function addComponents($deviceId)
    {
        $device = Device::with('deviceType.channels')->findOrFail($deviceId);
        return view('admin.components.create_for_device', compact('device'));
    }

    public function showComponents($deviceId)
    {
        $device = Device::with('components')->findOrFail($deviceId);
        return view('admin.components.index_for_device', compact('device'));
    }
    public function destroy(Component $component)
    {
        dd($component);
        $component->delete();

        return redirect()->route('components.index_for_device', $component->device_id)
                        ->with('success', __('Component deleted successfully.'));
    }

}