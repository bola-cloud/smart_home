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
    
        try {
            $response = $this->httpClient->get('/last-message', [
                'query' => ['topic' => $topic],
            ]);
    
            $result = json_decode($response->getBody(), true);
    
            if (!$result['success'] || $result['message'] === null) {
                return [
                    'status' => 'error',
                    'message' => 'No state received or topic not found',
                ];
            }
    
            return [
                'status' => 'success',
                'last_state' => json_decode($result['message'], true),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
    
}
