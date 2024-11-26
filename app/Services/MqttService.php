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
        Log::info("getLastMessage called for device {$deviceId}, component {$componentOrder}");
        
        $topic = "Mazaya/{$deviceId}/{$componentOrder}";
        $lastMessage = null;
        
        try {
            // Connect to MQTT broker
            $this->connect();
            
            // Subscribe to the topic
            Log::info("Subscribing to topic: {$topic}");
            $this->mqttClient->subscribe($topic, function (string $topic, string $message) use (&$lastMessage) {
                // Once message is received, store it
                $lastMessage = json_decode($message, true);
                Log::info("Message received on topic {$topic}: {$message}");
            }, MqttClient::QOS_AT_LEAST_ONCE);
    
            // Timeout Handling: Give it 10 seconds to receive the message, with a limit on loop count
            $startTime = time();
            $maxLoops = 50;  // Max attempts
            $loopCount = 0;
            while (time() - $startTime < 10 && $loopCount < $maxLoops) {
                $this->mqttClient->loop(100);  // Run the MQTT loop for 100ms
                if ($lastMessage !== null) {
                    break;  // If message received, break the loop
                }
                $loopCount++;
                Log::info("Waiting for message... Loop count: {$loopCount}");
            }
    
            // Check if the message was received or not
            if ($lastMessage !== null) {
                Log::info("Last state received: " . json_encode($lastMessage));
                // Return the response before disconnecting
                return response()->json([
                    'status' => 'success',
                    'last_state' => $lastMessage,
                ], 200);
            } else {
                Log::error("No message received after 10 seconds or topic not found. Last loop count: {$loopCount}");
                return response()->json([
                    'status' => 'error',
                    'message' => 'No state received or topic not found',
                ], 404);
            }
        } catch (MqttClientException $e) {
            // Handle MQTT client connection errors
            Log::error("MQTT Client Error: {$e->getMessage()}");
            return response()->json([
                'status' => 'error',
                'message' => 'MQTT client error: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            // Handle any other unexpected errors
            Log::error("Unexpected Error: {$e->getMessage()}");
            return response()->json([
                'status' => 'error',
                'message' => 'Unexpected error occurred: ' . $e->getMessage()
            ], 500);
        } finally {
            // Ensure that we give a delay before disconnecting
            sleep(3);  // Delay before disconnecting
            
            // Add additional check here to ensure the connection is still active before disconnecting
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
