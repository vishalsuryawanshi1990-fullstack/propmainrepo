<?php

namespace App\Services\Google;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Shared service-account plumbing for Google APIs authenticated via a
 * signed-JWT OAuth2 bearer grant. Reads the same FIREBASE_CREDENTIALS_PATH
 * service-account JSON already required for FCM push (see FcmPushService)
 * so Firebase Realtime Database (see FirebaseBroadcaster) reuses it too —
 * one Google account, not a second third-party vendor for realtime chat.
 */
class GoogleServiceAccount
{
    public function credentials(): ?array
    {
        $path = config('services.firebase.credentials_path');

        if (! $path || ! is_readable($path)) {
            return null;
        }

        $json = json_decode(file_get_contents($path), true);

        return isset($json['client_email'], $json['private_key'], $json['project_id']) ? $json : null;
    }

    /**
     * An OAuth2 access token for the given scope, obtained via the
     * service-account JWT-bearer grant (RFC 7523) and cached for its ~1hr
     * lifetime.
     */
    public function accessToken(string $scope): ?string
    {
        $credentials = $this->credentials();

        if ($credentials === null) {
            return null;
        }

        return Cache::remember('google:access-token:'.md5($scope), now()->addMinutes(55), function () use ($credentials, $scope) {
            $now = time();
            $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64UrlEncode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => $scope,
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

    /**
     * A Firebase Auth custom token for the given uid — signed locally per
     * the Admin SDK's custom-token spec, no network round trip. The client
     * exchanges it via signInWithCustomToken(), so Firebase Security Rules
     * can independently check `auth.uid` on the client's direct connection
     * to the Realtime Database (this backend's own writes go through the
     * "database" OAuth2 scope above, which bypasses rules entirely).
     *
     * @param  array<string, mixed>  $claims  Extra claims merged into the
     *                                        token's "claims" object (exposed to Security Rules as auth.token.<key>).
     */
    public function signCustomToken(string $uid, array $claims = []): ?string
    {
        $credentials = $this->credentials();

        if ($credentials === null) {
            return null;
        }

        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'sub' => $credentials['client_email'],
            'aud' => 'https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit',
            'iat' => $now,
            'exp' => $now + 3600,
            'uid' => $uid,
            'claims' => $claims,
        ]));

        $signingInput = "{$header}.{$payload}";
        openssl_sign($signingInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

        return $signingInput.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
