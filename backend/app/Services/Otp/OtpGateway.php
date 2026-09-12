<?php

namespace App\Services\Otp;

interface OtpGateway
{
    /**
     * Send an OTP code to the given phone number.
     */
    public function send(string $phone, string $otp): void;
}
