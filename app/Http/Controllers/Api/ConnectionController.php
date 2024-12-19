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
    
        // Authenticate user
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
    
        // Set fields and save the device
        $device->section_id = $request->section_id;
        $device->last_updated = Carbon::now();
        $device->activation = false;
        $device->user_id = $user->id;
        $device->serial = $device->id . '-' . rand(1000000, 9999999);
    
        if (!$device->save()) {
            return response()->json(['message' => 'Failed to update device'], 500);
        }
    
        // Dispatch the job with a 2-minute delay
        CheckDeviceActivationJob::dispatch($device->id)->delay(now()->addMinutes(2));
    
        // Process the device channels and components
        $channelsWithComponents = $device->deviceType->channels->groupBy('name')->map(function ($channels, $channelName) use ($device) {
            // Find components that match the order for any channel with the same name
            $matchingComponents = $device->components->filter(function ($component) use ($channels) {
                return $channels->pluck('order')->contains($component->order);
            })->map(function ($matchingComponent) {
                return [
                    'id' => $matchingComponent->id,
                    'name' => $matchingComponent->name,
                    'type' => $matchingComponent->type,
                    'order' => $matchingComponent->order,
                    'file_path' => $matchingComponent->file_path,
                    'manual' => $matchingComponent->manual,
                    'created_at' => $matchingComponent->created_at,
                    'updated_at' => $matchingComponent->updated_at,
                ];
            });
    
            return [
                'channel_name' => $channelName,
                'components' => $matchingComponents->values(),
            ];
        })->values();
    
        // Return the response with device details and channels
        return response()->json([
            'status' => 'Success',
            'message' => 'Device found and activation initiated',
            'data' => [
                'id' => $device->id,
                'name' => $device->name,
                'serial' => $device->serial,
                'section_id' => (int) $device->section_id, // Explicitly cast to integer
                'project_id' => optional($device->section)->project->id ?? null,
                'type' => $device->type,
                'activation' => $device->activation,
                'last_updated' => $device->last_updated,
                'ip' => $device->ip,
                'mac_address' => $device->mac_address,
                'channels' => $channelsWithComponents,
            ],
        ]);
    }
    
    public function confirmActivation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|exists:devices,id',
            'ip' => "required|ip",
            'mac_address' => ['nullable', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/'],
        ]);
    
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }
    
        $device = Device::findOrFail($request->device_id);
    
        if ($device->activation) {
            return response()->json([
                'status' => 'Already Activated',
                'message' => 'This device is already activated',
            ], 200);
        }
    
        if ($device->section_id !== null && $device->last_updated !== null && $device->serial !== null) {
            $device->update([
                'activation' => true,
                'ip' => $request->ip,
                'mac_address' => $request->mac_address,
            ]);
    
            return response()->json([
                'status' => 'Success',
                'message' => 'Device activation confirmed',
            ]);
        } else {
            return response()->json([
                'status' => 'Failed',
                'message' => 'Device cannot be activated because required fields are missing',
            ], 400);
        }
    }
    
}
