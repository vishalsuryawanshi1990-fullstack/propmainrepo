<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS Gateway
    |--------------------------------------------------------------------------
    |
    | Which provider actually sends the OTP SMS. "log" writes the OTP to the
    | log instead of sending anything — used for local/testing so the flow
    | can be exercised without real MSG91/Twilio credentials.
    |
    */

    'gateway' => env('SMS_OTP_PROVIDER', 'log'),

    'length' => 6,

    'ttl_seconds' => 300,

    'max_verify_attempts' => 5,

    'resend_cooldown_seconds' => 60,

    'msg91' => [
        'auth_key' => env('MSG91_AUTH_KEY'),
        'template_id' => env('MSG91_TEMPLATE_ID'),
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from_number' => env('TWILIO_FROM_NUMBER'),
    ],

];
