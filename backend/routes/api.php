<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(base_path('routes/api_v1.php'));

Route::prefix('webhooks')->group(base_path('routes/webhooks.php'));
