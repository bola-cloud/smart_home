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

    public function getLastStateFromDevice(Request $request)
    {
        $request->validate([
            'device_id' => 'required|integer',
            'component_order' => 'required|integer',
        ]);

        $deviceId = $request->device_id;
        $componentOrder = $request->component_order;

        $result = $this->mqttService->getLastMessage($deviceId, $componentOrder);

        return response()->json($result);
    }
}
