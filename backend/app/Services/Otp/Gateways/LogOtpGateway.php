<?php

namespace App\Services\Otp\Gateways;

use App\Services\Otp\OtpGateway;
use Illuminate\Support\Facades\Log;

/**
 * Writes the OTP to the log instead of sending an SMS. Used for local
 * development and testing so the auth flow works without real provider
 * credentials — never bind this gateway in production.
 */
class LogOtpGateway implements OtpGateway
{
    public function send(string $phone, string $otp): void
    {
        Log::info("[otp] {$phone} -> {$otp}");
    }
}
