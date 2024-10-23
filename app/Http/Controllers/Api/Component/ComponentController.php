<?php

namespace App\Http\Controllers\Api\Component;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Component;
use App\Models\Device;
use App\Models\Section;

class ComponentController extends Controller
{
    public function getComponents()
    {
        // Get the currently authenticated user or member
        $auth = Auth::user();
        $authType = $auth instanceof \App\Models\Member ? 'member' : 'user';
    
        if ($authType === 'user') {
            // If authenticated as a user, get components for all devices in the user's projects
            $components = Component::with(['device.section'])->whereHas('device', function ($query) use ($auth) {
                $query->whereHas('section.project', function ($projectQuery) use ($auth) {
                    $projectQuery->where('user_id', $auth->id);
                });
            })->get();
    
            // Embed section as part of the component object
            $componentsWithSections = $components->map(function ($component) {
                return [
                    'id' => $component->id,
                    'name' => $component->name,
                    'type' => $component->type,
                    'order' => $component->order,
                    'section' => [$component->device->section],  // Return section as an array
                    'created_at' => $component->created_at,
                    'updated_at' => $component->updated_at,
                ];
            });
    
            return response()->json([
                'status' => true,
                'message' => 'User components retrieved successfully',
                'data' => $componentsWithSections,
            ], 200);
    
        } elseif ($authType === 'member') {
            // If authenticated as a member, retrieve components for the devices in the member's devices column
    
            // Get device IDs from the member's devices column (assuming it's stored as an array or JSON)
            $deviceIds = $auth->devices;
    
            // Fetch components for those devices
            $components = Component::with(['device.section'])->whereIn('device_id', $deviceIds)->get();
    
            // Embed section as part of the component object
            $componentsWithSections = $components->map(function ($component) {
                return [
                    'id' => $component->id,
                    'name' => $component->name,
                    'type' => $component->type,
                    'order' => $component->order,
                    'section' => [$component->device->section],  // Return section as an array
                    'created_at' => $component->created_at,
                    'updated_at' => $component->updated_at,
                ];
            });
    
            return response()->json([
                'status' => true,
                'message' => 'Member components retrieved successfully',
                'data' => $componentsWithSections,
            ], 200);
        }
    
        return response()->json([
            'status' => false,
            'message' => 'Unknown authentication type',
        ], 400);
    }     
    
}