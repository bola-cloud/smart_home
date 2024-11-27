<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MqttService;

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

        // Return the response from the service
        return response()->json($result);
    }
    
}
