<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\Exceptions\MqttClientException;
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
        $topic = "Mazaya/{$deviceId}/{$componentOrder}";
        $lastMessage = null;
    
        try {
            // Connect to the MQTT broker
            $this->connect();
            echo "Connected to MQTT broker\n";
    
            // Subscribe to the topic
            $this->mqttClient->subscribe($topic, function (string $topic, string $message) use (&$lastMessage) {
                $lastMessage = json_decode($message, true); // Decode the received message
                echo "Message received: {$message}\n";
            }, MqttClient::QOS_AT_MOST_ONCE);
    
            // Run loop for 5 seconds, but break if a message is received
            $startTime = time();
            while (time() - $startTime < 5) { // Wait for up to 5 seconds
                $this->mqttClient->loop(100); // Process network events for 100ms
                if ($lastMessage !== null) {
                    break; // Exit the loop if a message is received
                }
            }
    
            // Disconnect from the broker
            $this->disconnect();
            echo "Disconnected from MQTT broker\n";
    
        } catch (MqttClientException $e) {
            echo "Failed to subscribe to topic: {$e->getMessage()}\n";
        }
    
        return $lastMessage;
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
