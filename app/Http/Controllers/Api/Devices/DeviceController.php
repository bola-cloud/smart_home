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
    public function getDevices()
    {
        // Get the currently authenticated user
        $user = Auth::user();
    
        // Collection to store devices with their components and access type
        $devicesWithComponents = collect();
    
        // Fetch projects where the user is a member
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
                    })->map(function ($component) use ($deviceComponentsAccess) {
                        return [
                            'id' => $component->id,
                            'name' => $component->name,
                            'type' => $component->type,
                            'order' => $component->order,
                            'access' => $deviceComponentsAccess[$component->id] ?? null, // Add access level
                            'created_at' => $component->created_at,
                            'updated_at' => $component->updated_at,
                        ];
                    });
    
                    // Check if all components in the member's access list exist on this device
                    $requestedComponentIds = array_keys($deviceComponentsAccess);
                    $actualComponentIds = $device->components->pluck('id')->toArray();
                    $invalidComponents = array_diff($requestedComponentIds, $actualComponentIds);
    
                    if (!empty($invalidComponents)) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Member does not have access to some components on this device',
                            'invalid_components' => $invalidComponents,
                        ], 403);
                    }
    
                    return [
                        'id' => $device->id,
                        'name' => $device->name,
                        'serial' => $device->serial,
                        'section_id' => $device->section_id,
                        'project_id' => optional($device->section)->project->id ?? null, // Ensure project exists
                        'type' => 'member', // Access type
                        'activation' => $device->activation,
                        'last_updated' => $device->last_updated,
                        'created_at' => $device->created_at,
                        'updated_at' => $device->updated_at,
                        'components' => $componentsWithAccess,
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
}
