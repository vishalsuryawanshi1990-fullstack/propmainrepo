<?php

namespace App\Services\Notifications;

use App\Services\Google\GoogleServiceAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FCM's legacy server-key API is retired — the current HTTP v1 API is
 * authenticated via a Google service-account OAuth2 JWT bearer grant, not
 * a static key (see GoogleServiceAccount).
 */
class FcmPushService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function __construct(private GoogleServiceAccount $google) {}

    public function send(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        $credentials = $this->google->credentials();

        if ($credentials === null) {
            Log::info('FCM push skipped — no service account configured.', compact('title')); // dev/no-op path

            return false;
        }

        $accessToken = $this->google->accessToken(self::SCOPE);

        if ($accessToken === null) {
            return false;
        }

        $response = Http::withToken($accessToken)->post(
            "https://fcm.googleapis.com/v1/projects/{$credentials['project_id']}/messages:send",
            [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => array_map(strval(...), $data),
                ],
            ],
        );

        if ($response->failed()) {
            Log::warning('FCM push failed', ['status' => $response->status(), 'body' => $response->body()]);
        }

        return $response->successful();
    }
}
