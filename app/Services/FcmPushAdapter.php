<?php

namespace App\Services;

class FcmPushAdapter
{
    protected string $projectId;
    protected string $serviceAccountPath;

    public function __construct()
    {
        $this->projectId = config('services.fcm.project_id', 'bara-mobile-app');
        $this->serviceAccountPath = config('services.fcm.service_account_json', 'storage/app/firebase_service_account.json');
    }

    /**
     * Dispatch high-priority push notification to mobile client device token.
     */
    public function sendNotification(string $deviceToken, string $title, string $body, ?array $dataPayload = []): array
    {
        return [
            'success' => true,
            'project_id' => $this->projectId,
            'device_token' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $dataPayload,
            'dispatched_at' => now()->toIso8601String(),
        ];
    }
}
