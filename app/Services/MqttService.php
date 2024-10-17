<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\Exceptions\MqttClientException;
use App\Models\Component;
use Carbon\Carbon;

class MqttService
{
    protected $mqttClient;

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

                // Update the component's updated_at field
                $this->updateComponentTimestamp($componentId);

            }, MqttClient::QOS_AT_MOST_ONCE);

            // Keep the connection alive
            $this->mqttClient->loop(true);

        } catch (MqttClientException $e) {
            echo "Failed to subscribe to topic: {$e->getMessage()}\n";
        }
    }

    public function updateComponentTimestamp($componentId)
    {
        $component = Component::find($componentId);
        if ($component) {
            $component->updated_at = Carbon::now();
            $component->save();
            echo "Component updated_at field updated: $componentId\n";
        } else {
            echo "Component not found: $componentId\n";
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
