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
        //Change the topic to be "Mazaya/{$deviceId}/{$componentOrder}"
        $component = Component::find($componentId);

        $topic = "Mazaya/{$deviceId}/{$component->order}";
        $message = json_encode(['action' => $action]);

        try {
            $this->mqttClient->publish($topic, $message);
            echo "Published {$action} to device {$deviceId}, component {$component->order}\n";
        } catch (MqttClientException $e) {
            echo "Failed to publish action: {$e->getMessage()}\n";
        }
    }

    public function getLastState($componentId)
    {
        $component = Component::find($componentId);
        if (!$component || !$component->device) {
            echo "Component or device not found for component ID {$componentId}\n";
            return null;
        }
    
        $deviceId = $component->device->id;
        $topic = "Mazaya/{$deviceId}/{$componentId}";
    
        try {
            $this->connect();
            $this->mqttClient->subscribe($topic, function (string $topic, string $message) use ($componentId) {
                $data = json_decode($message, true);
                if (is_array($data) && !empty($data)) {
                    $firstKey = array_key_first($data);
                    $this->lastStates[$componentId] = $data[$firstKey];
                }
            }, MqttClient::QOS_AT_MOST_ONCE);
    
            $this->mqttClient->loop(true, 5000); // Wait for 5 seconds to receive messages
            $this->disconnect();
    
        } catch (MqttClientException $e) {
            echo "Failed to subscribe to topic for last state: {$e->getMessage()}\n";
        }
    
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
