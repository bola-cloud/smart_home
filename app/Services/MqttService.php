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

    public function publishAction($deviceId, $componentId, $action)
    {
        //////////////////        Note    //////////////////////////////////////
        // Change the topic to be "Mazaya/{$deviceId}/{$componentOrder}"
        $component = Component::find($componentId);

        $topic = "Mazaya/{$deviceId}/{$component->order}";

        // Ensure the message is encoded as a JSON string
        $message = $action;

        try {
            // Publish the message after encoding to JSON
            $this->mqttClient->publish($topic, $message);
            echo "Published {$action} to device {$deviceId}, component {$component->order}\n";
        } catch (MqttClientException $e) {
            echo "Failed to publish action: {$e->getMessage()}\n";
        }
    }

    public function getLastState($deviceId, $componentOrder)
    {
        // Construct the topic to subscribe to
        $topic = "Mazaya/{$deviceId}/{$componentOrder}";
    
        // Variable to hold the last state (this will be returned)
        $lastState = null;
    
        try {
            // Connect to the MQTT broker
            $this->connect();
    
            // Subscribe to the topic and set up a callback to handle incoming messages
            $this->mqttClient->subscribe($topic, function (string $topic, string $message) use (&$lastState) {
                // When a message is received, decode it into an array
                $data = json_decode($message, true);
                
                // Ensure the data is valid and is an array
                if (is_array($data) && !empty($data)) {
                    $lastState = $data;  // Store the last state
                }
            }, MqttClient::QOS_AT_MOST_ONCE);
    
            // Wait for a message to be received (you can adjust the timeout as needed)
            $this->mqttClient->loop(true, 5000); // Wait for 5 seconds to receive messages
    
            // Disconnect from the MQTT broker after receiving the message
            $this->disconnect();
            
        } catch (MqttClientException $e) {
            echo "Failed to subscribe to topic for last state: {$e->getMessage()}\n";
        }
    
        // Return the last state (or null if no state received)
        return $lastState;
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
