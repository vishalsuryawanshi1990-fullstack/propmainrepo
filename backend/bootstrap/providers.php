<?php

use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\TelescopeServiceProvider;
use Laravel\Telescope\Telescope;

// laravel/telescope is require-dev — a production `composer install
// --no-dev` removes the package entirely, so App\Providers\TelescopeServiceProvider
// (which extends one of its classes) can't even be autoloaded there.
// TELESCOPE_ENABLED (config/telescope.php) already gates Telescope's own
// behavior once loaded; this gates whether it gets loaded at all.
return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    ...(class_exists(Telescope::class) ? [TelescopeServiceProvider::class] : []),
];
