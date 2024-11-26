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

    public function publishAction($deviceId, $componentId, $action, $retain = false)
    {
        $topic = "Mazaya/{$deviceId}/{$componentId}";
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
        $topic = "Mazaya/{$deviceId}/{$componentId}";

        $response = $this->httpClient->post('/subscribe', [
            'json' => [
                'topic' => $topic,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function getLastMessage($deviceId, $componentOrder)
    {
        $topic = "Mazaya/{$deviceId}/{$componentOrder}";

        $response = $this->httpClient->get('/last-message', [
            'query' => ['topic' => $topic],
        ]);

        return json_decode($response->getBody(), true);
    }
}
