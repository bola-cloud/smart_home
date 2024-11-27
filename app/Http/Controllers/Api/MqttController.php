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

        $result = $this->mqttService->publishAction($deviceId, $componentId, $message, true);

        return response()->json($result);
    }

    public function subscribeToTopic(Request $request)
    {
        dd($request->all());
        // Validate incoming data
        $request->validate([
            'device_id' => 'required|integer',
            'component_id' => 'required|integer',
        ]);

        // Get the validated data from the request
        $deviceId = $request->device_id;
        $componentId = $request->component_id;

        // Redirect the request to the service to subscribe to the topic
        $result = $this->mqttService->subscribeToTopic($deviceId, $componentId);

        // Return the response from the service
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
