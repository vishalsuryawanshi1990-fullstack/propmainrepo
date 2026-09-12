<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Monetization knobs (see 06-monetization-engine.md)
    |--------------------------------------------------------------------------
    |
    | These are env-backed for now. Doc 06/09 expect them to become
    | admin-configurable at runtime (a settings table) in Sprint 5 — treat
    | this file as the seed defaults for that store, not the final home.
    |
    */

    'free_signup_credits' => env('MONETIZATION_FREE_SIGNUP_CREDITS', 1),

    'max_daily_video_watches' => env('MONETIZATION_MAX_DAILY_VIDEO_WATCHES', 10),

    'video_watch_cooldown_seconds' => env('MONETIZATION_VIDEO_COOLDOWN_SECONDS', 120),

    'scratch_card_validity_hours' => env('MONETIZATION_SCRATCH_CARD_VALIDITY_HOURS', 48),

];
