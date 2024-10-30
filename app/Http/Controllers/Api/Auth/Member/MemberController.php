<?php

namespace App\Http\Controllers\Api\Auth\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\User;
use App\Models\Project;
use App\Models\Device;
use App\Models\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use OneSignal;

class MemberController extends Controller
{
    public function addMemberWithPermissions(Request $request)
    {
        // Validation
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
                    preg_match('/devices\.(\d+)\.components\.(\d+)\.component_id/', $attribute, $matches);
                    $deviceIndex = $matches[1];
                    $deviceId = data_get($request, "devices.{$deviceIndex}.device_id");
    
                    if (!Component::where('id', $value)->where('device_id', $deviceId)->exists()) {
                        $fail("The specified component does not exist on this device.");
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
    
        // Authenticate user and verify project ownership
        $user = Auth::user();
        $project = Project::where('id', $request->project_id)->where('user_id', $user->id)->first();
        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to add members to this project',
            ], 403);
        }
    
        // Identify member by email or phone
        $member = User::where('email', $request->member_identifier)
                      ->orWhere('phone_number', $request->member_identifier)
                      ->first();
    
        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'No user found with this email or phone number',
            ], 404);
        }
    
        // Format devices array for storage and gather device names
        $devicesArray = [];
        $deviceNames = [];
        foreach ($request->devices as $deviceData) {
            $device = Device::with('components')->find($deviceData['device_id']);
            $deviceNames[] = $device->name; // Collect device names for notification
    
            $componentsArray = [];
            foreach ($deviceData['components'] as $componentData) {
                $componentsArray[] = [
                    'component_id' => $componentData['component_id'],
                    'permission' => $componentData['permission'],
                ];
            }
            $devicesArray[] = [
                'device_id' => $device->id,
                'components' => $componentsArray,
            ];
        }
    
        // Save or update the member with permissions
        $existingMember = Member::where('member_id', $member->id)
                                ->where('project_id', $request->project_id)
                                ->first();
    
        if ($existingMember) {
            $existingMember->devices = $devicesArray;
            $existingMember->save();
        } else {
            $existingMember = Member::create([
                'owner_id' => $user->id,
                'member_id' => $member->id,
                'project_id' => $request->project_id,
                'devices' => $devicesArray,
            ]);
        }
        // Send notification
        $this->sendNotificationToUser($member->notification, $deviceNames);

        return response()->json([
            'status' => true,
            'message' => 'Member added successfully with permissions and notification sent',
            'data' => $existingMember->devices,
        ], 200);
    }
    
    /**
     * Helper method to send notification using OneSignal.
     */
    protected function sendNotificationToUser($notificationId, $deviceNames)
    {
        // Check for valid notification ID
        if (empty($notificationId)) {
            return response()->json([
                'status' => false,
                'message' => 'User does not have a valid notification ID',
            ], 400);
        }
    
        // Prepare notification data
        $notificationData = [
            "app_id" => env('ONESIGNAL_APP_ID'),
            "headings" => ["en" => "Access Granted to Project Devices"],
            "contents" => [
                "en" => "You have been granted access to devices: " . implode(', ', $deviceNames)
            ],
            "data" => [
                "type" => "access_granted",
                "devices" => $deviceNames
            ],
            "include_external_user_ids" => [$notificationId], // External ID from users table
        ];
    
        // Send notification with authorization token
        $client = new \GuzzleHttp\Client();
    
        $client->post('https://onesignal.com/api/v1/notifications', [
            'headers' => [
                'Authorization' => 'Bearer ' . env('ONESIGNAL_REST_API_KEY'),
                'Content-Type'  => 'application/json',
                'accept'  => 'application/json',
            ],
            'json' => $notificationData,
        ]);
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
