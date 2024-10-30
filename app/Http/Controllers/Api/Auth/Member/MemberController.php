<?php

namespace App\Http\Controllers\Api\Auth\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\User;
use App\Models\Project;
use App\Models\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class MemberController extends Controller
{
    public function addMemberWithPermissions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'member_identifier' => 'required|string',
            'project_id' => 'required|exists:projects,id',
            'devices' => 'required|array',
            'devices.*.device_id' => 'required|integer|exists:devices,id',
            'devices.*.components' => 'required|array',
            'devices.*.components.*.component_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($request) {
                    // Extract device index from the attribute string
                    preg_match('/devices\.(\d+)\.components\.(\d+)\.component_id/', $attribute, $matches);
                    $deviceIndex = $matches[1];
                    $deviceId = data_get($request, "devices.{$deviceIndex}.device_id");
        
                    if (!Component::where('id', $value)->where('device_id', $deviceId)->exists()) {
                        $fail("The specified device does not exist.");
                    }
                }
            ],
            'devices.*.components.*.permission' => 'required|string|in:view,control',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        // Get the authenticated user (owner)
        $user = Auth::user();
    
        // Check if the authenticated user owns the project
        $project = Project::where('id', $request->project_id)->where('user_id', $user->id)->first();
        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to add members to this project',
            ], 403);
        }
    
        // Retrieve the member by email or phone number
        $member = User::where('email', $request->member_identifier)
                      ->orWhere('phone_number', $request->member_identifier)
                      ->first();
    
        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'No user found with this email or phone number',
            ], 404);
        }
    
        // Format the devices array into an array of objects for storage
        $devicesArray = [];
        foreach ($request->devices as $deviceId => $components) {
            $componentsArray = [];
            foreach ($components as $componentId => $permission) {
                $componentsArray[] = [
                    'component_id' => $componentId,
                    'permission' => $permission,
                ];
            }
            $devicesArray[] = [
                'device_id' => $deviceId,
                'components' => $componentsArray,
            ];
        }
    
        // Check if the member already exists in the project
        $existingMember = Member::where('member_id', $member->id)
                                ->where('project_id', $request->project_id)
                                ->first();
    
        if ($existingMember) {
            // Update the devices with the new format if the member already exists
            $existingMember->devices = $devicesArray;
            $existingMember->save();
    
            return response()->json([
                'status' => true,
                'message' => 'Permissions updated successfully',
                'data' => $existingMember->devices,
            ], 200);
        }
    
        // If the member does not already exist, create a new entry with the specified permissions
        $newMember = Member::create([
            'owner_id' => $user->id,         // Set the owner to the currently authenticated user
            'member_id' => $member->id,      // Set the user receiving the permissions
            'project_id' => $request->project_id,  // Set the project the member has access to
            'devices' => $devicesArray,  // Store the devices as an array of objects
        ]);
    
        return response()->json([
            'status' => true,
            'message' => 'Member added successfully with permissions',
            'data' => $newMember->devices,  // Return devices with permissions
        ], 201);
    }
    
    public function grantFullAccessToMember(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'member_identifier' => 'required|string',  // Allow email or phone as identifier
            'project_id' => 'required|exists:projects,id',  // Ensure the project exists
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        // Get the authenticated user (owner)
        $user = Auth::user();
    
        // Check if the authenticated user owns the project
        $project = Project::where('id', $request->project_id)->where('user_id', $user->id)->first();
        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to add members to this project',
            ], 403);
        }
    
        // Retrieve the member by email or phone number
        $member = User::where('email', $request->member_identifier)
                      ->orWhere('phone_number', $request->member_identifier)
                      ->first();
    
        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'No user found with this email or phone number',
            ], 404);
        }
    
        // Retrieve all devices and components in the project
        $devices = $project->sections()->with('devices.components')->get()
            ->pluck('devices')
            ->flatten();
    
        // Format the devices array with full access for all components
        $devicesWithFullAccess = [];
        foreach ($devices as $device) {
            $componentsArray = [];
            foreach ($device->components as $component) {
                $componentsArray[] = [
                    'component_id' => $component->id,
                    'permission' => 'control',  // Grant full 'control' access to each component
                ];
            }
            $devicesWithFullAccess[] = [
                'device_id' => $device->id,
                'components' => $componentsArray,
            ];
        }
    
        // Check if the member already exists in the project
        $existingMember = Member::where('member_id', $member->id)
                                ->where('project_id', $request->project_id)
                                ->first();
    
        if ($existingMember) {
            // Update the devices with full access if the member already exists
            $existingMember->devices = $devicesWithFullAccess;
            $existingMember->save();
    
            return response()->json([
                'status' => true,
                'message' => 'Full access permissions granted successfully',
                'data' => $existingMember->devices,
            ], 200);
        }
    
        // If the member does not already exist, create a new entry with full access permissions
        $newMember = Member::create([
            'owner_id' => $user->id,
            'member_id' => $member->id,
            'project_id' => $request->project_id,
            'devices' => $devicesWithFullAccess,  // Store devices with full access as an array of objects
        ]);
    
        return response()->json([
            'status' => true,
            'message' => 'Member granted full access successfully',
            'data' => $newMember->devices,
        ], 201);
    }
    
    public function removeMember(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'member_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get the authenticated user
        $user = Auth::user();

        // Check if the user is the owner of the project
        $project = Project::where('id', $request->project_id)->where('user_id', $user->id)->first();

        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to remove members from this project',
            ], 403);
        }

        // Find the member in the project
        $member = Member::where('project_id', $request->project_id)
                        ->where('member_id', $request->member_id)
                        ->first();

        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'Member not found in this project',
            ], 404);
        }

        // Delete the member from the project
        $member->delete();

        return response()->json([
            'status' => true,
            'message' => 'Member removed from the project successfully',
        ], 200);
    }
}
