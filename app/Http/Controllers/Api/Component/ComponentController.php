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
        // Get the currently authenticated user
        $user = Auth::user();
    
        // Array to store components with their section and access information
        $componentsWithSections = collect();
    
        // Check if the user is the owner of any projects
        $ownedComponents = Component::with(['device.section'])
            ->whereHas('device.section.project', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();
    
        if ($ownedComponents->isNotEmpty()) {
            // Map through owned components
            $ownedComponentsWithSections = $ownedComponents->map(function ($component) {
                return [
                    'id' => $component->id,
                    'name' => $component->name,
                    'type' => $component->type,
                    'order' => $component->order,
                    'access' => 'owner', // Access type for owner
                    'section' => [$component->device->section],
                    'created_at' => $component->created_at,
                    'updated_at' => $component->updated_at,
                ];
            });
    
            $componentsWithSections = $componentsWithSections->merge($ownedComponentsWithSections);
        }
    
        // Retrieve projects where the user is a member
        $memberProjects = Member::where('member_id', $user->id)->get();
    
        if ($memberProjects->isNotEmpty()) {
            foreach ($memberProjects as $memberProject) {
                $memberDevices = $memberProject->devices;
                $deviceIds = array_keys($memberDevices);
    
                // Fetch components for the devices the member has access to
                $components = Component::with(['device.section'])
                    ->whereIn('device_id', $deviceIds)
                    ->get();
    
                // Add access levels for each component based on the devices' data
                $memberComponentsWithSections = $components->map(function ($component) use ($memberDevices) {
                    $deviceComponentsAccess = $memberDevices[$component->device_id] ?? [];
                    $accessibility = $deviceComponentsAccess[$component->id] ?? null;
    
                    return [
                        'id' => $component->id,
                        'name' => $component->name,
                        'type' => $component->type,
                        'order' => $component->order,
                        'access' => $accessibility, // Access level from member's devices data
                        'section' => [$component->device->section],
                        'created_at' => $component->created_at,
                        'updated_at' => $component->updated_at,
                    ];
                });
    
                $componentsWithSections = $componentsWithSections->merge($memberComponentsWithSections);
            }
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Components retrieved successfully',
            'data' => $componentsWithSections->unique('id')->values(), // Ensure unique components
        ], 200);
    }    
}