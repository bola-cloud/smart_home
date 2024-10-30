<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\CheckDeviceActivationJob;

class ConnectionController extends Controller
{
    public function connectMobile(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'section_id' => 'required|exists:sections,id',
            'device_type_id' => 'required|exists:device_types,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }
    
        // Get the authenticated user
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }
    
        // Find an inactive device that matches the criteria
        $device = Device::where('activation', 0)
                        ->where('device_type_id', $request->device_type_id)
                        ->whereNull('last_updated')
                        ->whereNull('section_id')
                        ->first();
    
        if (!$device) {
            return response()->json(['message' => 'No available device found'], 404);
        }
    
        // Update the device details with section_id, last_updated, and activation
        $device->update([
            'section_id' => $request->section_id,
            'last_updated' => Carbon::now(),
            'activation' => false,  // Initially not activated
            'user_id'=> $user->id,
            'serial' => $device->id . '-' . rand(1000000, 9999999),
        ]);
    
        // Schedule the CheckDeviceActivationJob to run after 1 minute
        CheckDeviceActivationJob::dispatch($device->id)->delay(now()->addMinute());
    
        shell_exec('php /home/george/htdocs/smartsystem.mazaya-iot.org/artisan queue:work --stop-when-empty > /dev/null 2>&1 &');
        
        // Respond with the device details
        return response()->json([
            'status' => 'Success',
            'message' => 'Device found and activation initiated',
            'data' => [
                'device_id' => $device->id,
                'device_serial' => $device->serial,
                'section_id' => $request->section_id,
            ]
        ]);
    }    

    public function confirmActivation(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|exists:devices,id',
            'ip' => "required|ip"
        ]);
    
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }
    
        // Get the device
        $device = Device::findOrFail($request->device_id);
    
        // Check if the device is already activated
        if ($device->activation) {
            return response()->json([
                'status' => 'Already Activated',
                'message' => 'This device is already activated',
            ], 200);
        }
    
        // Confirm activation within the 1-minute window
        if ($device->section_id !== null && $device->last_updated !== null && $device->serial !== null ) {
            // All required columns are not null, proceed with activation
            $device->update([
                'activation' => true, // Final confirmation of activation
                'ip' => $request->ip,
            ]);
    
            return response()->json([
                'status' => 'Success',
                'message' => 'Device activation confirmed',
            ]);
        } else {
            // If any of the columns are null, return an error response
            return response()->json([
                'status' => 'Failed',
                'message' => 'Device cannot be activated because required fields are missing',
            ], 400);
        }
    }
    
}
