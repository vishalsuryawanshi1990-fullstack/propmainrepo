<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * OTPs are ephemeral by nature, so they live in cache (not a DB table) as a
 * hashed code plus a verify-attempt counter, keyed by phone number.
 */
class OtpService
{
    public function __construct(private readonly OtpGateway $gateway) {}

    public function issue(string $phone): void
    {
        $otp = str_pad((string) random_int(0, 10 ** config('otp.length') - 1), config('otp.length'), '0', STR_PAD_LEFT);

        Cache::put($this->key($phone), [
            'hash' => Hash::make($otp),
            'attempts' => 0,
        ], now()->addSeconds(config('otp.ttl_seconds')));

        Cache::put($this->cooldownKey($phone), true, now()->addSeconds(config('otp.resend_cooldown_seconds')));

        $this->gateway->send($phone, $otp);
    }

    public function isOnCooldown(string $phone): bool
    {
        return Cache::has($this->cooldownKey($phone));
    }

    public function verify(string $phone, string $otp): bool
    {
        $entry = Cache::get($this->key($phone));

        if (! $entry) {
            return false;
        }

        if ($entry['attempts'] >= config('otp.max_verify_attempts')) {
            Cache::forget($this->key($phone));

            return false;
        }

        if (! Hash::check($otp, $entry['hash'])) {
            $entry['attempts']++;
            Cache::put($this->key($phone), $entry, now()->addSeconds(config('otp.ttl_seconds')));

            return false;
        }

        Cache::forget($this->key($phone));

        return true;
    }

    private function key(string $phone): string
    {
        return "otp:{$phone}";
    }

    private function cooldownKey(string $phone): string
    {
        return "otp:{$phone}:cooldown";
    }
}
