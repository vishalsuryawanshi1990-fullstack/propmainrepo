<?php

use Illuminate\Support\Facades\Route;

// Base path /api/v1 — see 04-api-specification.md for the full endpoint list.
// Each group below is a placeholder for a backend sprint from 07-backend-tasks-laravel.md.

Route::get('/ping', fn () => response()->apiSuccess(['pong' => true]));

// Sprint 1 — Auth & Users
// Route::post('/auth/otp/request', ...);
// Route::post('/auth/otp/verify', ...);
// Route::post('/auth/register', ...);
// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/auth/logout', ...);
//     Route::get('/me', ...);
//     Route::patch('/me', ...);
// });

// Sprint 2 — Property Catalog
// Route::get('/properties', ...);
// Route::get('/properties/{property}', ...);
// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/properties', ...);
// });

// Sprint 3 — Monetization Engine (see 06-monetization-engine.md)
// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/wallet', ...);
//     Route::post('/unlocks/check', ...);
//     Route::post('/unlocks/spend', ...);
//     Route::post('/video-ads/request-token', ...);
//     Route::post('/coupons/purchase', ...);
//     Route::post('/scratch-cards/{scratchCard}/scratch', ...);
// });
// Route::post('/video-ads/ssv-callback', ...); // called by AdMob's servers, not the app

// Sprint 4 — Chat & Notifications
// Sprint 5 — Admin APIs
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin|moderator'])
    ->group(base_path('routes/admin.php'));
