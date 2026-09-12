<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use App\Http\Controllers\Api\V1\KycDocumentController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PublicProfileController;
use Illuminate\Support\Facades\Route;

// Base path /api/v1 — see 04-api-specification.md for the full endpoint list.
// Each group below maps to a backend sprint from 07-backend-tasks-laravel.md.

Route::get('/ping', fn () => response()->apiSuccess(['pong' => true]));

// Sprint 1 — Auth & Users
Route::post('/auth/otp/request', [OtpController::class, 'request'])->middleware('throttle:otp-request');
Route::post('/auth/otp/verify', [OtpController::class, 'verify']);
Route::get('/users/{user}/public-profile', [PublicProfileController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    Route::get('/me', [ProfileController::class, 'show']);
    Route::patch('/me', [ProfileController::class, 'update']);
    Route::get('/me/kyc-documents', [KycDocumentController::class, 'index']);
    Route::post('/me/kyc-documents', [KycDocumentController::class, 'store']);
});

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
//     Route::post('/unlocks/spend', ...)->middleware('throttle:unlock-spend');
//     Route::post('/video-ads/request-token', ...);
//     Route::post('/coupons/purchase', ...);
//     Route::post('/scratch-cards/{scratchCard}/scratch', ...);
// });
// Route::post('/video-ads/ssv-callback', ...); // called by AdMob's servers, not the app

// Sprint 4 — Chat & Notifications
// Sprint 5 — Admin APIs
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin|moderator'])
    ->group(base_path('routes/admin.php'));
