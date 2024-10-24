<?php

namespace App\Http\Controllers\Api\Devices;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Device;
use App\Models\Section;

class DeviceController extends Controller
{
    public function getDevices()
    {
        // Get the currently authenticated user or member
        $auth = Auth::user();
        $authType = $auth instanceof \App\Models\Member ? 'member' : 'user';
    
        if ($authType === 'user') {
            // If authenticated as a user, get all devices in the sections of the projects they own
    
            // Fetch all sections belonging to the user's projects
            $sections = Section::whereHas('project', function ($query) use ($auth) {
                $query->where('user_id', $auth->id);
            })->get();
    
            // Get all devices within these sections and load their related components
            $devices = Device::with('components','section.project')->whereIn('section_id', $sections->pluck('id'))->get();
    
            // Add components to the response
            $devicesWithComponents = $devices->map(function ($device) {
                return [
                    'id' => $device->id,
                    'name' => $device->name,
                    'serial' => $device->serial,
                    'section_id' => $device->section_id,
                    'project_id' => $device->section()->project->id,
                    'activation' => $device->activation,
                    'last_updated' => $device->last_updated,
                    'created_at' => $device->created_at,
                    'updated_at' => $device->updated_at,
                    'components' => $device->components // Add the related components
                ];
            });
    
            return response()->json([
                'status' => true,
                'message' => 'User devices retrieved successfully',
                'data' => $devicesWithComponents,
            ], 200);
    
        } elseif ($authType === 'member') {
            // If authenticated as a member, retrieve devices from the 'devices' column
            $memberDevices = $auth->devices; // This is assumed to be stored as a JSON/array in the DB
    
            if (!$memberDevices || !is_array($memberDevices)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No devices found for the member',
                    'data' => null,
                ], 404);
            }
    
            // Extract device IDs
            $deviceIds = array_keys($memberDevices);
    
            // Fetch devices based on the member's device IDs and load their related components
            $devices = Device::with('components')->whereIn('id', $deviceIds)->get();
    
            // Add components and accessability to the response
            $devicesWithComponents = $devices->map(function ($device) use ($memberDevices) {
                // Get component access levels for this device
                $deviceComponentsAccess = $memberDevices[$device->id] ?? [];
    
                // Map components with access level
                $componentsWithAccess = $device->components->map(function ($component) use ($deviceComponentsAccess) {
                    return [
                        'id' => $component->id,
                        'name' => $component->name,
                        'type' => $component->type,
                        'order' => $component->order,
                        'access' => $deviceComponentsAccess[$component->id] ?? null, // Add access (view/control)
                        'created_at' => $component->created_at,
                        'updated_at' => $component->updated_at,
                    ];
                });
    
                return [
                    'id' => $device->id,
                    'name' => $device->name,
                    'serial' => $device->serial,
                    'section_id' => $device->section_id,
                    'activation' => $device->activation,
                    'last_updated' => $device->last_updated,
                    'created_at' => $device->created_at,
                    'updated_at' => $device->updated_at,
                    'components' => $componentsWithAccess // Add the related components with access level
                ];
            });
    
            return response()->json([
                'status' => true,
                'message' => 'Member devices retrieved successfully',
                'data' => $devicesWithComponents,
            ], 200);
        }
    
        return response()->json([
            'status' => false,
            'message' => 'Unknown authentication type',
        ], 400);
    }    
    
}
