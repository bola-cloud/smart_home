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
            // Clear existing devices and set the new devices format
            $existingMember->devices = $request->devices;
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

        // Prepare the devices data with full access permissions
        $devicesWithFullAccess = [];
        foreach ($devices as $device) {
            $devicePermissions = [];
            foreach ($device->components as $component) {
                $devicePermissions[$component->id] = 'control';  // Grant full 'control' access to each component
            }
            $devicesWithFullAccess[$device->id] = $devicePermissions;
        }

        // Check if the member already exists in the project
        $existingMember = Member::where('member_id', $member->id)
                                ->where('project_id', $request->project_id)
                                ->first();

        if ($existingMember) {
            // Update the devices with full access permissions if the member already exists
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
            'owner_id' => $user->id,         // Set the owner to the currently authenticated user
            'member_id' => $member->id,      // Set the user receiving the permissions
            'project_id' => $request->project_id,  // Set the project the member has access to
            'devices' => $devicesWithFullAccess,   // Store the devices with full access as JSON
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Member granted full access successfully',
            'data' => [
                'member' => $newMember,
                'devices' => $newMember->devices,  // Return devices with full access permissions
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
