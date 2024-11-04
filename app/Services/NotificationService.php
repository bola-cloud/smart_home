<?php

namespace App\Services;

use GuzzleHttp\Client;

class NotificationService
{
    protected $client;
    protected $appId;
    protected $restApiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->appId = env('ONESIGNAL_APP_ID');
        $this->restApiKey = env('ONESIGNAL_REST_API_KEY');
    }

    public function sendToUser($notificationId, $title, $message, array $deviceNames = [])
    {
        if (empty($notificationId)) {
            return [
                'status' => false,
                'message' => 'User does not have a valid notification ID',
            ];
        }

        return $this->sendNotification([
            'include_external_user_ids' => [$notificationId],
            'title' => $title,
            'message' => $message,
            'deviceNames' => $deviceNames,
        ]);
    }

    public function sendToAllUsers($title, $message, array $deviceNames = [])
    {
        return $this->sendNotification([
            'included_segments' => ['Total Subscriptions'],
            'title' => $title,
            'message' => $message,
            'deviceNames' => $deviceNames,
        ]);
    }

    protected function sendNotification(array $options)
    {
        $notificationData = [
            "app_id" => $this->appId,
            "headings" => ["en" => $options['title']],
            "contents" => [
                "en" => $options['message'] . (isset($options['deviceNames']) ? implode(', ', $options['deviceNames']) : '')
            ],
            "data" => [
                "type" => "access_granted",
            ]
        ];

        if (isset($options['include_external_user_ids'])) {
            $notificationData['include_external_user_ids'] = $options['include_external_user_ids'];
        } elseif (isset($options['included_segments'])) {
            $notificationData['included_segments'] = $options['included_segments'];
        }

        try {
            $response = $this->client->post('https://onesignal.com/api/v1/notifications', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->restApiKey,
                    'Content-Type' => 'application/json',
                    'accept' => 'application/json',
                ],
                'json' => $notificationData,
            ]);

            if ($response->getStatusCode() == 200) {
                return [
                    'status' => true,
                    'message' => 'Notification sent successfully',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to send notification',
                'error' => $e->getMessage(),
            ];
        }
    }
}
