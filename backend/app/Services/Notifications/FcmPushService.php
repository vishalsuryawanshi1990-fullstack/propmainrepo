<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FCM's legacy server-key API is retired — the current HTTP v1 API is
 * authenticated via a Google service-account OAuth2 JWT bearer grant, not
 * a static key. No google/apiclient dependency: the JWT assertion is
 * small enough to sign directly with openssl.
 */
class FcmPushService
{
    private const TOKEN_CACHE_KEY = 'fcm:access-token';

    public function send(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        $credentials = $this->credentials();

        if ($credentials === null) {
            Log::info('FCM push skipped — no service account configured.', compact('title')); // dev/no-op path

            return false;
        }

        $accessToken = $this->accessToken($credentials);

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

    private function accessToken(array $credentials): ?string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(55), function () use ($credentials) {
            $now = time();
            $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64UrlEncode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $signingInput = "{$header}.{$claims}";
            openssl_sign($signingInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
            $jwt = $signingInput.'.'.$this->base64UrlEncode($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            return $response->successful() ? $response->json('access_token') : null;
        });
    }

    private function credentials(): ?array
    {
        $path = config('services.firebase.credentials_path');

        if (! $path || ! is_readable($path)) {
            return null;
        }

        $json = json_decode(file_get_contents($path), true);

        return isset($json['client_email'], $json['private_key'], $json['project_id']) ? $json : null;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
