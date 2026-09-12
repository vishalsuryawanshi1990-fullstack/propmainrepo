<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local
     * environments. Horizon's dashboard route authenticates via the
     * "web" session guard, not Sanctum — reaching it at all in
     * production means a session-based admin login (or an
     * infra-level Basic Auth / IP allowlist in front of it, which is
     * the more common real-world setup) needs to exist; this gate is
     * only the authorization check on top of whichever of those gets
     * built. Role-based rather than a hardcoded email allowlist, since
     * doc01 makes "admin" a real role, not a fixed set of people.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            return (bool) $user?->hasRole('admin');
        });
    }
}
