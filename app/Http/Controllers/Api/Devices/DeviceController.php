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
    
        // Collection to store devices with their channels and components
        $devicesWithChannels = collect();
    
        // Retrieve devices for projects the user owns
        $ownedDevices = Device::with(['components', 'section.project', 'deviceType.channels'])
            ->whereHas('section.project', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();
    
        // Map the devices with channels and matched components for owners
        $ownedDevicesWithChannels = $ownedDevices->map(function ($device) {
            $channelsWithComponents = $device->deviceType->channels->groupBy('name')->map(function ($channels, $channelName) use ($device) {
                // Find all components that match the order for any channel with the same name
                $matchingComponents = $device->components->filter(function ($component) use ($channels) {
                    return $channels->pluck('order')->contains($component->order);
                })->map(function ($matchingComponent) {
                    return [
                        'id' => $matchingComponent->id,
                        'name' => $matchingComponent->name,
                        'type' => $matchingComponent->type,
                        'order' => $matchingComponent->order,
                        'created_at' => $matchingComponent->created_at,
                        'updated_at' => $matchingComponent->updated_at,
                    ];
                });
    
                return [
                    'channel_name' => $channelName,
                    'components' => $matchingComponents->values(),
                ];
            })->values();
    
            return [
                'id' => $device->id,
                'name' => $device->name,
                'serial' => $device->serial,
                'section_id' => $device->section_id,
                'project_id' => optional($device->section)->project->id ?? null,
                'type' => 'owner',
                'activation' => $device->activation,
                'last_updated' => $device->last_updated,
                'ip' => $device->ip,
                'mac_address' => $device->mac_address,
                'created_at' => $device->created_at,
                'updated_at' => $device->updated_at,
                'channels' => $channelsWithComponents,
            ];
        });
    
        $devicesWithChannels = $devicesWithChannels->merge($ownedDevicesWithChannels);
    
        // Process devices where the user is a member with specific permissions
        $memberProjects = Member::where('member_id', $user->id)->get();
    
        if ($memberProjects->isNotEmpty()) {
            foreach ($memberProjects as $memberProject) {
                $memberDevices = collect($memberProject->devices)->keyBy('device_id'); // Key by device_id for easy lookup
                $deviceIds = $memberDevices->keys()->toArray();
    
                // Fetch devices from the member's device list
                $devices = Device::with(['components', 'section.project', 'deviceType.channels'])->whereIn('id', $deviceIds)->get();
    
                $memberDevicesWithChannels = $devices->map(function ($device) use ($memberDevices) {
                    $devicePermissions = $memberDevices[$device->id]['components'] ?? [];
    
                    $channelsWithComponents = $device->deviceType->channels->groupBy('name')->map(function ($channels, $channelName) use ($device, $devicePermissions) {
                        // Find all components with matching order and permissions for this channel name
                        $matchingComponents = $device->components->filter(function ($component) use ($channels, $devicePermissions) {
                            $hasPermission = collect($devicePermissions)->firstWhere('component_id', $component->id);
                            return $hasPermission && $channels->pluck('order')->contains($component->order);
                        })->map(function ($matchingComponent) use ($devicePermissions) {
                            $permission = collect($devicePermissions)->firstWhere('component_id', $matchingComponent->id);
                            return [
                                'id' => $matchingComponent->id,
                                'name' => $matchingComponent->name,
                                'type' => $matchingComponent->type,
                                'order' => $matchingComponent->order,
                                'permission' => $permission['permission'] ?? null,
                                'created_at' => $matchingComponent->created_at,
                                'updated_at' => $matchingComponent->updated_at,
                            ];
                        });
    
                        return [
                            'channel_name' => $channelName,
                            'components' => $matchingComponents->values(),
                        ];
                    })->filter(function ($channel) {
                        return $channel['components']->isNotEmpty(); // Remove channels with no accessible components
                    })->values();
    
                    return [
                        'id' => $device->id,
                        'name' => $device->name,
                        'serial' => $device->serial,
                        'section_id' => $device->section_id,
                        'project_id' => optional($device->section)->project->id ?? null,
                        'type' => 'member',
                        'activation' => $device->activation,
                        'last_updated' => $device->last_updated,
                        'ip' => $device->ip,
                        'mac_address' => $device->mac_address,
                        'created_at' => $device->created_at,
                        'updated_at' => $device->updated_at,
                        'channels' => $channelsWithComponents,
                    ];
                });
    
                $devicesWithChannels = $devicesWithChannels->merge($memberDevicesWithChannels);
            }
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Devices retrieved successfully',
            'data' => $devicesWithChannels->unique('id')->values(),
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
