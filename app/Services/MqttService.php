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
        // Generate a unique client ID based on device ID or timestamp
        $uniqueClientId = 'mqtt-laravel-mazaya-' . uniqid();
        
        $host = '91.108.102.82'; // Replace with your MQTT broker IP
        $port = 1883;             // Default MQTT port
        
        // Initialize the MQTT client with the unique client ID
        $this->mqttClient = new MqttClient($host, $port, $uniqueClientId);
    }
    
    public function connect()
    {
        try {
            $this->mqttClient->connect();
            Log::info("Connected to MQTT broker with client ID: " . $this->mqttClient->getClientId());
        } catch (MqttClientException $e) {
            Log::error("Failed to connect to MQTT broker: {$e->getMessage()}");
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
        Log::info("getLastMessage called for device {$deviceId}, component {$componentOrder}");
    
        $topic = "Mazaya/{$deviceId}/{$componentOrder}";
        $lastMessage = null;
    
        try {
            // Connect to MQTT broker if not already connected
            if (!$this->mqttClient->isConnected()) {
                $this->connect();
            }
    
            // Subscribe to the topic
            Log::info("Subscribing to topic: {$topic}");
            $this->mqttClient->subscribe($topic, function (string $topic, string $message) use (&$lastMessage) {
                // Store the last message when received
                $lastMessage = json_decode($message, true);
                Log::info("Message received on topic {$topic}: {$message}");
            }, MqttClient::QOS_AT_LEAST_ONCE);
    
            // Timeout Handling: Wait for up to 10 seconds or until we receive the message
            $startTime = time();
            while (time() - $startTime < 10) {
                $this->mqttClient->loop(500); // Run the MQTT loop for 500ms
                if ($lastMessage !== null) {
                    break; // If message received, break the loop
                }
            }
    
            // Check if the message was received or not
            if ($lastMessage !== null) {
                Log::info("Last state received: " . json_encode($lastMessage));
                // Return the response (controller should handle the response)
                return $lastMessage;  // Return just the message here
            } else {
                Log::error("No message received after 10 seconds or topic not found.");
                // Return error state (controller should handle the response)
                return null;  // Return null if no message received
            }
        } catch (MqttClientException $e) {
            Log::error("MQTT Client Error: {$e->getMessage()}");
            // Handle MQTT client connection errors
            return null;
        } catch (\Exception $e) {
            Log::error("Unexpected Error: {$e->getMessage()}");
            // Handle any other unexpected errors
            return null;
        } finally {
            // Ensure that we give a longer delay before disconnecting
            sleep(5);  // Increase delay before disconnecting if necessary
    
            // Check if we are connected before disconnecting
            if ($this->mqttClient->isConnected()) {
                Log::info("Disconnecting from MQTT broker...");
                $this->disconnect();
            } else {
                Log::warning("MQTT client is already disconnected.");
            }
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
