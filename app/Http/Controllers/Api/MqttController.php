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
        dd($message);
        // Connect to the MQTT broker
        $this->mqttService->connect();

        // Publish the message on the topic Mazaya/device_id/component_id
        // Ensure the message is a valid JSON string before publishing
        $this->mqttService->publishAction($deviceId, $componentId, $message);

        // Disconnect from the MQTT broker
        $this->mqttService->disconnect();

        return response()->json([
            'status' => 'success',
            'message' => 'Message published successfully',
        ]);
    }

    public function subscribeFromDevice(Request $request)
    {
        $request->validate([
            'device_id' => 'required|integer',
            'component_id' => 'required|integer',
        ]);
    }
}
