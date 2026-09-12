<?php

namespace App\Services\Otp\Gateways;

use App\Services\Otp\OtpGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Msg91OtpGateway implements OtpGateway
{
    public function send(string $phone, string $otp): void
    {
        $response = Http::withHeaders(['authkey' => config('otp.msg91.auth_key')])
            ->post('https://control.msg91.com/api/v5/otp', [
                'template_id' => config('otp.msg91.template_id'),
                'mobile' => $phone,
                'otp' => $otp,
            ]);

        if ($response->failed()) {
            Log::error('MSG91 OTP send failed', ['phone' => $phone, 'response' => $response->body()]);

            throw new \RuntimeException('Failed to send OTP SMS.');
        }
    }
}
