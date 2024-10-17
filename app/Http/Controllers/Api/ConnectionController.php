<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Device;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class ConnectionController extends Controller
{
    public function connectMobile(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'section_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }

        // Get the authenticated user
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Get the device that hasn't been activated and has no last_updated value
        $device = Device::where('activation', 0)
                        ->whereNull('last_updated')
                        ->first();

        if (!$device) {
            return response()->json(['message' => 'No available device found'], 404);
        }

        // Update the device with the user's section_id and set last_updated timestamp
        $device->update([
            'section_id' => $request->section_id,
            'last_updated' => Carbon::now(), // Set current timestamp
            'activation' => 1, // Optional: if you want to activate the device
            'serial' => $device->id . '-' . rand(1000000, 9999999), // Use device ID + random number for serial
            'user_id'=>$user->id,
        ]);

        // Respond with the user and device details
        $response = response()->json([
            'status' => 'Success',
            'message' => 'Request received successfully',
            'data' => [
                'device_id' => $device->id,
                'user_id' => $user->id,
                'device_serial' => $device->serial,
                'section_id' => $request->section_id,
            ]
        ]);
        return $response;
    }
}
