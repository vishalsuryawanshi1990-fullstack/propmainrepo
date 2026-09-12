<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Signup fraud controls (05-security-compliance.md)
    |--------------------------------------------------------------------------
    |
    | "Device fingerprinting + IP velocity checks on signup to slow down
    | fake-account farms created purely to harvest free unlock credits."
    |
    */

    'max_signups_per_device_per_day' => env('SECURITY_MAX_SIGNUPS_PER_DEVICE_PER_DAY', 3),

];
