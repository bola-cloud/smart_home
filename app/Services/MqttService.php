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
        // Topic format using both deviceId and componentId
        $topic = "Mazaya/{$deviceId}/{$componentId}";
        $message = json_encode(['action' => $action]);

        try {
            $this->mqttClient->publish($topic, $message);
            echo "Published {$action} to device {$deviceId}, component {$componentId}\n";
        } catch (MqttClientException $e) {
            echo "Failed to publish action: {$e->getMessage()}\n";
        }
    }

    public function subscribeToComponentTopics()
    {
        // Subscribe to all topics for all devices and components using wildcards
        $topic = 'Mazaya/+/+'; // Listens to all device/component topics

        try {
            $this->mqttClient->subscribe($topic, function (string $topic, string $message) {
                echo "Received message on topic [$topic]: $message\n";

                // Extract device_id and component_id from the topic
                $topicParts = explode('/', $topic);
                $deviceId = $topicParts[1]; // Device ID extracted
                $componentId = $topicParts[2]; // Component ID extracted

                // Store or update the component state
                $this->updateComponentState($componentId, $message);

            }, MqttClient::QOS_AT_MOST_ONCE);

            // Keep the connection alive
            $this->mqttClient->loop(true);

        } catch (MqttClientException $e) {
            echo "Failed to subscribe to topic: {$e->getMessage()}\n";
        }
    }

    public function updateComponentState($componentId, $message)
    {
        // Here you can handle the state update logic.
        // Assume that the message contains the state as `status`
        $data = json_decode($message, true);
        if (isset($data['status'])) {
            // Store the last state in memory
            $this->lastStates[$componentId] = $data['status'];
            echo "Component state updated: {$componentId}\n";
        }
    }

    public function getLastState($componentId)
    {
        // Retrieve the last state stored from the topic
        return isset($this->lastStates[$componentId]) ? $this->lastStates[$componentId] : null;
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
