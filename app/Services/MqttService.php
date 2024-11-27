<?php

namespace App\Services;

use GuzzleHttp\Client;

class MqttService
{
    protected $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client(['base_uri' => 'http://localhost:3000']);
    }

    public function publishAction($deviceId, $componentOrder, $action, $retain = true)
    {
        $topic = "Mazaya/{$deviceId}/{$componentOrder}";
        $message = json_encode(['action' => $action]);

        $response = $this->httpClient->post('/publish', [
            'json' => [
                'topic' => $topic,
                'message' => $message,
                'retain' => $retain,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function subscribeToTopic($deviceId, $componentId)
    {
        // Construct the topic string
        $topic = "Mazaya/{$deviceId}/{$componentId}";

        // Send a request to the Express server to subscribe to the topic
        $response = $this->httpClient->post('/subscribe', [
            'json' => [
                'device_id' => $deviceId,
                'component_id' => $componentId,
            ],
        ]);

        // Return the response from the Express server
        return json_decode($response->getBody(), true);
    }

    /**
     * Get the last message for a specific topic
     */
    public function getLastMessage($deviceId, $componentId)
    {
        // Construct the topic string
        $topic = "Mazaya/{$deviceId}/{$componentId}";

        // Send a request to the Express server to get the last message
        $response = $this->httpClient->post('/get-last-message', [
            'json' => [
                'device_id' => $deviceId,
                'component_id' => $componentId,
            ],
        ]);

        // Return the response from the Express server (the last message)
        return json_decode($response->getBody(), true);
    }  
}
