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
        $topic = "Mazaya/{$deviceId}/{$componentId}";
        $message = json_encode(['action' => $action]);

        try {
            $this->mqttClient->publish($topic, $message);
            echo "Published {$action} to device {$deviceId}, component {$componentId}\n";
        } catch (MqttClientException $e) {
            echo "Failed to publish action: {$e->getMessage()}\n";
        }
    }

    public function getLastState($componentId)
    {
        // Retrieve the component and associated device
        $component = Component::find($componentId);

        if (!$component || !$component->device) {
            echo "Component or device not found for component ID {$componentId}\n";
            return null;
        }

        $deviceId = $component->device->id;
        $topic = "Mazaya/{$deviceId}/{$componentId}";

        // Connect and subscribe to the specific topic
        try {
            $this->connect();
            $this->mqttClient->subscribe($topic, function (string $topic, string $message) use ($componentId) {
                echo "Received message on topic [$topic]: $message\n";

                // Decode the JSON message
                $data = json_decode($message, true);

                // Store only the value of the first key found in the JSON
                if (is_array($data) && !empty($data)) {
                    $firstKey = array_key_first($data);
                    $this->lastStates[$componentId] = $data[$firstKey];
                    echo "Component state updated for component ID {$componentId}: {$this->lastStates[$componentId]}\n";
                }
            }, MqttClient::QOS_AT_MOST_ONCE);

            // Run the loop briefly to wait for any incoming message
            $this->mqttClient->loop(true, 5000); // 5000ms to wait for message

            // Disconnect after receiving the message
            $this->disconnect();

        } catch (MqttClientException $e) {
            echo "Failed to subscribe to topic for last state: {$e->getMessage()}\n";
        }

        // Return the last state for the component if available
        return $this->lastStates[$componentId] ?? null;
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
