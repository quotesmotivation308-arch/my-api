<?php

namespace App\Services;

use App\Models\NotificationDeliveryAttempt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ExpoPushNotificationProvider implements PushNotificationProvider
{
    public function send(
        NotificationDeliveryAttempt $attempt,
        array $payload
    ): array {

        $token = $attempt->userDevice->device_token;

        $response = Http::withOptions([
                'proxy' => 'http://127.0.0.1:10808',
            ])
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(15)
            ->post(
                'https://exp.host/--/api/v2/push/send',
                [
                    [
                        'to' => $token,
                        'sound' => 'default',
                        'title' => $payload['title'] ?? 'Glish',
                        'body' => $payload['body'] ?? 'New word available',
                        'data' => $payload['data'] ?? [],
                    ]
                ]
            );


        if (!$response->successful()) {
            return [
                'success' => false,
                'provider' => 'expo',
                'error_message' => $response->body(),
            ];
        }


        $json = $response->json();

        $result = $json['data'][0] ?? null;


        if (!$result || $result['status'] !== 'ok') {

            return [
                'success' => false,
                'provider' => 'expo',
                'error_message' => json_encode($json),
            ];
        }


        return [
            'success' => true,
            'provider' => 'expo',
            'provider_message_id' => $result['id'] ?? (string) Str::uuid(),
        ];
    }
}