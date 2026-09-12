<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sprint 8 — routine maintenance (doc07's "monitoring dashboards live
// before go-live" implies these need to actually run, not just exist).
Schedule::command('horizon:snapshot')->everyFiveMinutes();
Schedule::command('telescope:prune --hours=48')->daily();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
