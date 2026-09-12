<?php

namespace App\Services\Monetization;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * 05-security-compliance.md: "AdMob Server-Side Verification (SSV) for
 * rewarded videos — the wallet credit is granted only after the signed
 * SSV callback, never from a client 'ad finished' event."
 *
 * Google's callback is a signed GET with query params including key_id
 * and signature. Verification per Google's documented algorithm:
 *   1. Fetch Google's current public keys (cached).
 *   2. The signed content is everything in the query string BEFORE the
 *      "signature" parameter, taken verbatim (not re-encoded).
 *   3. Verify with ECDSA/SHA-256 against the key matching key_id.
 */
class AdMobSsvVerifier
{
    private const CACHE_KEY = 'admob:verifier-keys';

    public function verify(Request $request): bool
    {
        $query = $request->query();

        if (! isset($query['key_id'], $query['signature'])) {
            return false;
        }

        $pem = $this->findKeyPem((int) $query['key_id']);

        if ($pem === null) {
            return false;
        }

        $content = $this->signedContent($request);
        $signature = $this->base64UrlDecode((string) $query['signature']);

        if ($content === null || $signature === false) {
            return false;
        }

        return openssl_verify($content, $signature, $pem, OPENSSL_ALGO_SHA256) === 1;
    }

    /**
     * Everything in the raw query string before "signature=", exactly as
     * received — re-encoding it from a parsed array risks a mismatch with
     * what Google actually signed.
     */
    private function signedContent(Request $request): ?string
    {
        $raw = $request->server->get('QUERY_STRING') ?? '';
        $position = strpos($raw, 'signature=');

        if ($position === false) {
            return null;
        }

        return rtrim(substr($raw, 0, $position), '&');
    }

    private function findKeyPem(int $keyId): ?string
    {
        $keys = Cache::remember(self::CACHE_KEY, now()->addHours(6), function () {
            $response = Http::get(config('services.admob.verifier_keys_url'));

            return $response->successful() ? ($response->json('keys') ?? []) : [];
        });

        foreach ($keys as $key) {
            if ((int) ($key['keyId'] ?? -1) === $keyId) {
                return $key['pem'] ?? null;
            }
        }

        return null;
    }

    private function base64UrlDecode(string $value): string|false
    {
        return base64_decode(strtr($value, '-_', '+/'));
    }
}
