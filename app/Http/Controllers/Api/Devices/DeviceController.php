<?php

namespace App\Http\Controllers\Api\Devices;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Device;
use App\Models\Section;

class DeviceController extends Controller
{
    // Return devices for the authenticated user or member
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

            // Get all devices within these sections
            $devices = Device::whereIn('section_id', $sections->pluck('id'))->get();

            return response()->json([
                'status' => true,
                'message' => 'User devices retrieved successfully',
                'data' => $devices,
            ], 200);

        } elseif ($authType === 'member') {
            // If authenticated as a member, retrieve devices from the 'devices' column

            // Get device IDs from the member's devices column (assuming it's stored as an array or JSON)
            $deviceIds = $auth->devices;

            // Fetch devices based on the member's device IDs
            $devices = Device::whereIn('id', $deviceIds)->get();

            return response()->json([
                'status' => true,
                'message' => 'Member devices retrieved successfully',
                'data' => $devices,
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Unknown authentication type',
        ], 400);
    }
}
