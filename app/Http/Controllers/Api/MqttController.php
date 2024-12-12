<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MqttService;
use App\Models\Component;

class MqttController extends Controller
{
    protected $mqttService;

    public function __construct(MqttService $mqttService)
    {
        $this->mqttService = $mqttService;
    }

    public function publishToDevice(Request $request)
    {
        $request->validate([
            'device_id' => 'required|integer',
            'component_id' => 'required|integer',
            'message' => 'required|array',
        ]);

        $deviceId = $request->device_id;
        $componentId = $request->component_id;
        $message = $request->message;
        $component = Component::find($componentId);

        $result = $this->mqttService->publishAction($deviceId, $component->order, $message, true);

        return response()->json($result);
    }

    public function subscribeToTopic(Request $request)
    {
        // Validate incoming data
        $request->validate([
            'device_id' => 'required|integer',
            'component_id' => 'required|integer',
        ]);
    
        // Get the validated data from the request
        $deviceId = $request->device_id;
        $componentId = $request->component_id;
    
        $component = Component::find($componentId);
    
        // Redirect the request to the service to subscribe to the topic
        $result = $this->mqttService->subscribeToTopic($deviceId, $component->order);
    
        // Check if a last message exists and transform it into JSON
        if (isset($result['last_message'])) {
            $lastMessage = $result['last_message'];
    
            // Attempt to decode the last message as JSON
            $decodedMessage = json_decode($lastMessage, true);
    
            if (json_last_error() === JSON_ERROR_NONE) {
                // If decoding is successful, replace the last message with the decoded JSON
                $result['last_message'] = $decodedMessage;
            } else {
                // If decoding fails, log a warning and leave the last message as-is
                \Log::warning('Failed to decode last_message as JSON', ['last_message' => $lastMessage]);
            }
        }
    
        // Return the transformed response
        return response()->json($result);
    }    

    public function getLastMessage(Request $request)
    {
        // Validate incoming data
        $request->validate([
            'device_id' => 'required|integer',
            'component_id' => 'required|integer',
        ]);

        // Get the validated data from the request
        $deviceId = $request->device_id;
        $componentId = $request->component_id;

        // Redirect the request to the service to fetch the last message
        $result = $this->mqttService->getLastMessage($deviceId, $componentId);

        // Return the response from the service
        return response()->json($result);
    }
    
}
