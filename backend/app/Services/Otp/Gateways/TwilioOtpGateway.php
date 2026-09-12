<?php

namespace App\Services\Otp\Gateways;

use App\Services\Otp\OtpGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioOtpGateway implements OtpGateway
{
    public function send(string $phone, string $otp): void
    {
        $sid = config('otp.twilio.sid');

        $response = Http::asForm()
            ->withBasicAuth($sid, config('otp.twilio.auth_token'))
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'To' => $phone,
                'From' => config('otp.twilio.from_number'),
                'Body' => "Your EstateConnect verification code is {$otp}. It expires in 5 minutes.",
            ]);

        if ($response->failed()) {
            Log::error('Twilio OTP send failed', ['phone' => $phone, 'response' => $response->body()]);

            throw new \RuntimeException('Failed to send OTP SMS.');
        }
    }
}
