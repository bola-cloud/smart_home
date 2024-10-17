<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Services\MqttService;

class MqttController extends Controller
{
    protected $mqttService;

    public function __construct(MqttService $mqttService)
    {
        $this->mqttService = $mqttService;
    }

    public function startListening()
    {
        // Connect to the MQTT broker
        $this->mqttService->connect();

        // Subscribe to component topics and handle messages
        $this->mqttService->subscribeToComponentTopics();

        // Disconnect (this would usually run continuously, but can disconnect for a test)
        $this->mqttService->disconnect();
    }
}
