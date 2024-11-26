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
        Log::info("Subscribing to topic: {$topic}");
    
        try {
            // Connect to the MQTT broker
            Log::info("Attempting to connect to MQTT broker");
            $this->connect();
            Log::info("Connected to MQTT broker");
    
            // Subscribe to the topic
            Log::info("Subscribing to topic: {$topic}");
            $this->mqttClient->subscribe($topic, function (string $topic, string $message) use (&$lastMessage) {
                $lastMessage = json_decode($message, true); // Decode the received message
                Log::info("Message received: {$message}");
            }, MqttClient::QOS_AT_MOST_ONCE);
    
            // Run the loop to wait for the message
            Log::info("Starting MQTT loop to wait for messages");
            $startTime = time();
            while (time() - $startTime < 5) { // Wait for up to 5 seconds
                $this->mqttClient->loop(100); // Process network events for 100ms
                if ($lastMessage !== null) {
                    Log::info("Exiting loop after receiving message");
                    break; // Exit the loop if a message is received
                }
            }
    
            // Disconnect after receiving the message
            Log::info("Disconnecting from MQTT broker");
            $this->disconnect();
            Log::info("Disconnected from MQTT broker");
    
            // If a message was received, return it, otherwise return an error
            if ($lastMessage !== null) {
                Log::info("Returning last message: " . json_encode($lastMessage));
                return $lastMessage;
            } else {
                Log::error("No message received within the timeout period");
                return null;
            }
    
        } catch (MqttClientException $e) {
            Log::error("Failed to subscribe to topic: {$e->getMessage()}");
            return null;
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
