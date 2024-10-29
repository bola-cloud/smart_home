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
    
        if ($existingMember) {
            // Get current permissions stored in the devices column
            $existingDevices = $existingMember->devices;
            $allDevicesMatch = true;
    
            foreach ($request->devices as $deviceId => $components) {
                $missingComponents = [];
                $deviceFullyMatched = true;
    
                // Check if the device already exists in the member's devices
                if (isset($existingDevices[$deviceId])) {
                    foreach ($components as $componentId => $permission) {
                        if (isset($existingDevices[$deviceId][$componentId])) {
                            // Check if the existing permission matches
                            if ($existingDevices[$deviceId][$componentId] !== $permission) {
                                // Update permission if it does not match
                                $existingDevices[$deviceId][$componentId] = $permission;
                                $deviceFullyMatched = false;
                            }
                        } else {
                            // Component does not exist, add it
                            $missingComponents[$componentId] = $permission;
                            $deviceFullyMatched = false;
                        }
                    }
    
                    // Add any missing components for this device
                    foreach ($missingComponents as $componentId => $permission) {
                        $existingDevices[$deviceId][$componentId] = $permission;
                    }
                } else {
                    // Device does not exist, so add it entirely
                    $existingDevices[$deviceId] = $components;
                    $deviceFullyMatched = false;
                }
    
                // If this device fully matched, keep `allDevicesMatch` as true; otherwise, set to false
                if (!$deviceFullyMatched) {
                    $allDevicesMatch = false;
                }
            }
    
            // After looping, check if all devices matched exactly
            if ($allDevicesMatch) {
                return response()->json([
                    'status' => false,
                    'message' => 'All components for all devices already have the specified permissions',
                ], 409);
            }
    
            // Save the updated devices data for the member
            $existingMember->devices = $existingDevices;
            $existingMember->save();
    
            return response()->json([
                'status' => true,
                'message' => 'Permissions updated successfully',
                'data' => $existingDevices,
            ], 200);
        }
    
        // If member does not exist, create a new member entry with specified permissions
        $newMember = Member::create([
            'owner_id' => $user->id,         // Set the owner to the currently authenticated user
            'member_id' => $member->id,      // Set the user receiving the permissions
            'project_id' => $request->project_id,  // Set the project the member has access to
            'devices' => $request->devices,  // Store the devices as JSON
        ]);
    
        return response()->json([
            'status' => true,
            'message' => 'Member added successfully with permissions',
            'data' => [
                'member' => $newMember,
                'devices' => $newMember->devices,  // Return devices with permissions
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
