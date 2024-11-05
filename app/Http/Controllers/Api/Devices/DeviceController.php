<?php

namespace App\Http\Controllers\Api\Devices;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Device;
use App\Models\Member;
use App\Models\Section;

class DeviceController extends Controller
{
    public function getDevices(Request $request)
    {
        // Get the currently authenticated user
        $user = Auth::user();

        // Collection to store devices with their components and access type
        $devicesWithComponents = collect();

        // 1. Retrieve devices for projects the user owns
        $ownedDevices = Device::with('components', 'section.project')
            ->whereHas('section.project', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        // Map the devices with components for owner and convert components to objects
        $ownedDevicesWithComponents = $ownedDevices->map(function ($device) {
            // Convert components to an associative array
            $componentsAsArray = $device->components->map(function ($component) {
                return [
                    'id' => $component->id,
                    'name' => $component->name,
                    'type' => $component->type,
                    'order' => $component->order,
                    'created_at' => $component->created_at,
                    'updated_at' => $component->updated_at,
                ];
            })->values(); // Ensure it's a simple array without keys
                  

            return [
                'id' => $device->id,
                'name' => $device->name,
                'serial' => $device->serial,
                'section_id' => $device->section_id,
                'project_id' => optional($device->section)->project->id ?? null,
                'type' => 'owner', // Access type
                'activation' => $device->activation,
                'last_updated' => $device->last_updated,
                'ip' => $device->ip,
                'mac_address' => $device->mac_address,
                'created_at' => $device->created_at,
                'updated_at' => $device->updated_at,
                'components' => $componentsAsArray, // Array of objects
            ];            
        });
        $devicesWithComponents = $devicesWithComponents->merge($ownedDevicesWithComponents);

        // 2. Retrieve devices where the user is a member with specific permissions
        $memberProjects = Member::where('member_id', $user->id)->get();

        if ($memberProjects->isNotEmpty()) {
            foreach ($memberProjects as $memberProject) {
                $memberDevices = $memberProject->devices;
                $deviceIds = array_keys($memberDevices);

                // Fetch devices from the member's device list
                $devices = Device::with('components', 'section.project')->whereIn('id', $deviceIds)->get();

                // Add components with member-specific permissions
                $memberDevicesWithComponents = $devices->map(function ($device) use ($memberDevices) {
                    $deviceComponentsAccess = $memberDevices[$device->id] ?? [];

                    // Filter components to include only those specified in member's access list
                    $componentsWithAccess = $device->components->filter(function ($component) use ($deviceComponentsAccess) {
                        return array_key_exists($component->id, $deviceComponentsAccess);
                    })->mapWithKeys(function ($component) use ($deviceComponentsAccess) {
                        return [
                            $component->id => [
                                'id' => $component->id,
                                'name' => $component->name,
                                'type' => $component->type,
                                'order' => $component->order,
                                'access' => $deviceComponentsAccess[$component->id] ?? null, // Add access level
                                'created_at' => $component->created_at,
                                'updated_at' => $component->updated_at,
                            ]
                        ];
                    })->values();

                    return [
                        'id' => $device->id,
                        'name' => $device->name,
                        'serial' => $device->serial,
                        'section_id' => $device->section_id,
                        'project_id' => optional($device->section)->project->id ?? null, // Ensure project exists
                        'type' => 'member', // Access type
                        'activation' => $device->activation,
                        'last_updated' => $device->last_updated,
                        'ip' => $device->ip,
                        'mac_address' => $device->mac_address,
                        'created_at' => $device->created_at,
                        'updated_at' => $device->updated_at,
                        'components' => $componentsWithAccess, // Components as objects with access level
                    ];
                });

                $devicesWithComponents = $devicesWithComponents->merge($memberDevicesWithComponents);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Devices retrieved successfully',
            'data' => $devicesWithComponents->unique('id')->values(), // Ensure unique devices
        ], 200);
    }
    
    public function editDeviceName(Request $request, Device $device)
    {
        // Validate input
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Check if the authenticated user is the owner of the project this device belongs to
        if ($device->section->project->user_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to edit this device',
            ], 403);
        }

        // Update the device name
        $device->name = $request->name;
        $device->save();

        return response()->json([
            'status' => true,
            'message' => 'Device name updated successfully',
            'data' => $device,
        ], 200);
    }

    public function deleteDevice(Device $device)
    {
        if(auth()->check()) {
            if(auth()->user()->id == $device->user_id){
                //inactivate device from the user
                $device->update([
                    'user_id'=> null ,
                    'section_id'=> null ,
                    'serial'=> null ,
                    'last_updated' => null ,
                    'activation' => false ,
                    'ip' => null ,
                    'mac_address' => null ,
                ]);
                return response()->json([
                    'status' => true,
                    'message' => 'Section has been delted successfully',
                ], 200);
            }
        } else{
            return response()->json([
                'status' => false,
                'message' => 'You must be logged in to delete a device',
            ], 401);
        }
    }
}
