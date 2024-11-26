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
            'message' => 'required|array', // Ensure message is an array
        ]);
    
        $deviceId = $request->device_id;
        $componentId = $request->component_id;
        $message = $request->message;
    
        // Debugging - you can comment this out later
    
        // JSON encode the message before passing it to MQTT
        $messageJson = json_encode($message); 

        // Connect to the MQTT broker
        $this->mqttService->connect();
    
        // Publish the message on the topic Mazaya/device_id/component_id
        // Publish the JSON-encoded message
        $this->mqttService->publishAction($deviceId, $componentId, $messageJson);
    
        // Disconnect from the MQTT broker
        $this->mqttService->disconnect();
    
        return response()->json([
            'status' => 'success',
            'message' => 'Message published successfully',
        ]);
    }
    

    public function subscribeFromDevice(Request $request)
    {
        dd('jahsa');
        $request->validate([
            'component_id' => 'required|integer',
        ]);
        $this->mqttService->getLastState($request->component_id);
        return response()->json([
            'status' => 'success',
            'message' => 'Last state retrieved successfully',
        ]);
    }
}
