<?php

namespace App\Http\Controllers\Api\Auth\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\User;
use App\Models\Project;
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
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'member_identifier' => 'required|string',  // Allow email or phone as identifier
            'project_id' => 'required|exists:projects,id',  // Ensure the project exists
            'devices' => 'required|array',  // This will hold the device and component permissions
            'devices.*' => 'array',  // Each device should have components with permissions
            'devices.*.*' => 'string|in:view,control',  // Permissions can be either 'view' or 'control'
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
    
        // Check if the member already exists in the project
        $existingMember = Member::where('member_id', $member->id)
                                ->where('project_id', $request->project_id)
                                ->first();
    
        // Scenario 1: If member already exists, update devices and components as needed
        if ($existingMember) {
            $existingDevices = $existingMember->devices;
    
            foreach ($request->devices as $deviceId => $components) {
                // Check if the device already exists for the member
                if (isset($existingDevices[$deviceId])) {
                    $missingComponents = [];
                    $allComponentsMatch = true;
            
                    // Iterate through each component and check if it matches the existing permissions
                    foreach ($components as $componentId => $permission) {
                        if (isset($existingDevices[$deviceId][$componentId])) {
                            // Check if the existing permission matches
                            if ($existingDevices[$deviceId][$componentId] !== $permission) {
                                // Update the permission if it differs
                                $existingDevices[$deviceId][$componentId] = $permission;
                                $allComponentsMatch = false;
                            }
                        } else {
                            // Component does not exist, mark it as missing
                            $missingComponents[$componentId] = $permission;
                            $allComponentsMatch = false;
                        }
                    }
            
                    // If all components match for the device, return the 'already exists' message
                    if ($allComponentsMatch) {
                        return response()->json([
                            'status' => false,
                            'message' => 'All components for this device already have the specified permissions',
                            'device_id' => $deviceId,
                        ], 409);
                    } elseif (!empty($missingComponents)) {
                        // Add missing components if they were not previously set
                        foreach ($missingComponents as $componentId => $permission) {
                            $existingDevices[$deviceId][$componentId] = $permission;
                        }
                    }
                } else {
                    // If the device does not exist, add it with all specified components
                    $existingDevices[$deviceId] = $components;
                }
            }
                    
    
            // Update the devices in the database
            $existingMember->devices = $existingDevices;
            $existingMember->save();
    
            return response()->json([
                'status' => true,
                'message' => 'Member permissions updated successfully',
                'data' => [
                    'member' => $existingMember,
                    'devices' => $existingMember->devices,
                ],
            ], 200);
        }
    
        // Scenario 5: Member does not exist, create new member entry with permissions
        $newMember = Member::create([
            'owner_id' => $user->id,
            'member_id' => $member->id,
            'project_id' => $request->project_id,
            'devices' => $request->devices,
        ]);
    
        return response()->json([
            'status' => true,
            'message' => 'Member added successfully with permissions',
            'data' => [
                'member' => $newMember,
                'devices' => $newMember->devices,
            ],
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
