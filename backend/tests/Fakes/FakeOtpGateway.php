<?php

namespace Tests\Fakes;

use App\Services\Otp\OtpGateway;

/**
 * Captures the OTP instead of sending an SMS, so feature tests can read it
 * back rather than parsing logs.
 */
class FakeOtpGateway implements OtpGateway
{
    /** @var array<string, string> */
    public static array $sent = [];

    public function send(string $phone, string $otp): void
    {
        static::$sent[$phone] = $otp;
    }
}
