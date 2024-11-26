<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\Exceptions\MqttClientException;
use Illuminate\Support\Facades\Log;
use App\Models\Component;
use Carbon\Carbon;

class MqttService
{
    protected $mqttClient;
    protected $lastStates = []; // To store the latest state per component

    public function __construct()
    {
        $host = '91.108.102.82'; // Replace with your MQTT broker IP
        $port = 1883;             // Default MQTT port
        $clientId = 'mqtt-laravel-mazaya'; // Unique client ID
        $this->mqttClient = new MqttClient($host, $port, $clientId);
    }

    public function connect()
    {
        try {
            $this->mqttClient->connect();
            echo "Connected to MQTT broker\n";
        } catch (MqttClientException $e) {
            echo "Failed to connect to MQTT broker: {$e->getMessage()}\n";
        }
    }

    public function publishAction($deviceId, $componentId, $action, $retain = false)
    {
        // Find the component and construct the topic
        $component = Component::find($componentId);
        $topic = "Mazaya/{$deviceId}/{$component->order}";
        $message = json_encode(['action' => $action]);
    
        try {
            // Publish with the retain flag set to true if desired
            $this->mqttClient->publish($topic, $message, MqttClient::QOS_AT_MOST_ONCE, $retain);
            echo "Published {$action} to device {$deviceId}, component {$component->order}\n";
        } catch (MqttClientException $e) {
            echo "Failed to publish action: {$e->getMessage()}\n";
        }
    }

    public function getLastMessage($deviceId, $componentOrder)
    {
        Log::info("getLastMessage called");
        $topic = "Mazaya/{$deviceId}/{$componentOrder}";
        $lastMessage = null;
    
        try {
            // Connect to the MQTT broker
            $this->connect();
    
            // Subscribe to the topic
            $this->mqttClient->subscribe($topic, function (string $topic, string $message) use (&$lastMessage) {
                // Once a message is received, store it and stop the loop
                $lastMessage = json_decode($message, true);
                Log::info("Message received: {$message}");
            }, MqttClient::QOS_AT_LEAST_ONCE);
    
            // Run the loop and wait for up to 5 seconds for the message
            $startTime = time();
            while (time() - $startTime < 5) {
                $this->mqttClient->loop(100);  // Run the MQTT client loop for 100ms
                if ($lastMessage !== null) {
                    break; // If the message is received, break the loop
                }
            }
    
            // Disconnect from the broker
            $this->disconnect();
    
            // Return the last message if available
            if ($lastMessage !== null) {
                return response()->json([
                    'status' => 'success',
                    'last_state' => $lastMessage,
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No state received or topic not found',
                ], 404);
            }
    
        } catch (MqttClientException $e) {
            Log::error("MQTT Error: {$e->getMessage()}");
            return response()->json([
                'status' => 'error',
                'message' => 'MQTT client error'
            ], 500);
        }
    }
    
    public function disconnect()
    {
        try {
            $this->mqttClient->disconnect();
            echo "Disconnected from MQTT broker\n";
        } catch (MqttClientException $e) {
            echo "Failed to disconnect from MQTT broker: {$e->getMessage()}\n";
        }
    }
}
