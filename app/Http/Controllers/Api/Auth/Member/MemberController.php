<?php

namespace App\Http\Controllers\Api\Auth\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
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
            // Update the devices permissions if the member already exists
            $existingMember->devices = $request->devices;
            $existingMember->save();
    
            return response()->json([
                'status' => true,
                'message' => 'Member permissions updated successfully',
                'data' => [
                    'member' => $existingMember,
                    'devices' => $existingMember->devices,  // Return devices with permissions
                ],
            ], 200);
        }
    
        // Create a new member entry with the specified permissions
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
    
}
