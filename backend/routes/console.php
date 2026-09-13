<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sprint 8 — routine maintenance (doc07's "monitoring dashboards live
// before go-live" implies these need to actually run, not just exist).
Schedule::command('telescope:prune --hours=48')->daily();
Schedule::command('sanctum:prune-expired --hours=24')->daily();

/**
 * Shared hosting has no persistent Horizon/queue-worker daemon — this is
 * the standard Laravel workaround: the cron-driven scheduler (already
 * running every minute for the two lines above) also runs the queue for
 * up to ~50s at a time, processing DetectDuplicateListingJob/
 * SanitizeUploadedImageJob/queued notification mail in short bursts
 * instead of instantly. withoutOverlapping() stops a slow minute from
 * stacking two workers on top of each other.
 *
 * If this ever moves to a VPS with `php artisan horizon` running as a
 * real daemon (see docker/supervisor/horizon.conf), remove this line —
 * Horizon replaces it, not complements it.
 */
Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
