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
    
        // Array to store devices with their related components and access type
        $devicesWithComponents = collect();
    
        // Check if the user is an owner of any projects
        $ownedSections = Section::whereHas('project', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->pluck('id');
    
        if ($ownedSections->isNotEmpty()) {
            // Fetch all devices within owned sections
            $ownedDevices = Device::with('components', 'section.project')
                ->whereIn('section_id', $ownedSections)
                ->get();
    
            // Map the devices with components for owner
            $ownedDevicesWithComponents = $ownedDevices->map(function ($device) {
                return [
                    'id' => $device->id,
                    'name' => $device->name,
                    'serial' => $device->serial,
                    'section_id' => $device->section_id,
                    'project_id' => $device->section->project->id,
                    'type' => 'owner', // Access type
                    'activation' => $device->activation,
                    'last_updated' => $device->last_updated,
                    'created_at' => $device->created_at,
                    'updated_at' => $device->updated_at,
                    'components' => $device->components,
                ];
            });
    
            $devicesWithComponents = $devicesWithComponents->merge($ownedDevicesWithComponents);
        }
    
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
    
                    $componentsWithAccess = $device->components->map(function ($component) use ($deviceComponentsAccess) {
                        return [
                            'id' => $component->id,
                            'name' => $component->name,
                            'type' => $component->type,
                            'order' => $component->order,
                            'access' => $deviceComponentsAccess[$component->id] ?? null,
                            'created_at' => $component->created_at,
                            'updated_at' => $component->updated_at,
                        ];
                    });
    
                    return [
                        'id' => $device->id,
                        'name' => $device->name,
                        'serial' => $device->serial,
                        'section_id' => $device->section_id,
                        'project_id' => $device->section->project->id,
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
