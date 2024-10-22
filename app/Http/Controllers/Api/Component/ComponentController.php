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
    // Return components for the authenticated user or member
    public function getComponents()
    {
        // Get the currently authenticated user or member
        $auth = Auth::user();
        $authType = $auth instanceof \App\Models\Member ? 'member' : 'user';

        if ($authType === 'user') {
            // If authenticated as a user, get components for all devices in the user's projects
            $components = Component::whereHas('device', function ($query) use ($auth) {
                $query->whereHas('section.project', function ($projectQuery) use ($auth) {
                    $projectQuery->where('user_id', $auth->id);
                });
            })->get();

            return response()->json([
                'status' => true,
                'message' => 'User components retrieved successfully',
                'data' => $components,
            ], 200);

        } elseif ($authType === 'member') {
            // If authenticated as a member, retrieve components for the devices in the member's devices column

            // Get device IDs from the member's devices column (assuming it's stored as an array or JSON)
            $deviceIds = $auth->devices;

            // Fetch components for those devices
            $components = Component::whereIn('device_id', $deviceIds)->get();

            return response()->json([
                'status' => true,
                'message' => 'Member components retrieved successfully',
                'data' => $components,
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Unknown authentication type',
        ], 400);
    }
}