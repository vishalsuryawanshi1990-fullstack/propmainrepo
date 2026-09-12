<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\CmsController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\KycDocumentController;
use App\Http\Controllers\Api\V1\LocalityController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\PropertyMediaController;
use App\Http\Controllers\Api\V1\PublicProfileController;
use App\Http\Controllers\Api\V1\ScratchCardController;
use App\Http\Controllers\Api\V1\UnlockController;
use App\Http\Controllers\Api\V1\VideoAdController;
use App\Http\Controllers\Api\V1\WalletController;
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
Route::get('/properties', [PropertyController::class, 'index']);
Route::get('/properties/featured', [PropertyController::class, 'featured']);
Route::get('/properties/{property}', [PropertyController::class, 'show']);
Route::get('/properties/{property}/similar', [PropertyController::class, 'similar']);
Route::get('/localities/{locality}/insights', [LocalityController::class, 'insights']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me/properties', [PropertyController::class, 'myListings']);
    Route::post('/properties', [PropertyController::class, 'store']);
    Route::patch('/properties/{property}', [PropertyController::class, 'update']);
    Route::patch('/properties/{property}/status', [PropertyController::class, 'updateStatus']);
    Route::delete('/properties/{property}', [PropertyController::class, 'destroy']);
    Route::post('/properties/{property}/report', [PropertyController::class, 'report']);

    Route::post('/properties/{property}/favorite', [FavoriteController::class, 'store']);
    Route::delete('/properties/{property}/favorite', [FavoriteController::class, 'destroy']);
    Route::get('/favorites', [FavoriteController::class, 'index']);

    Route::get('/properties/{property}/{type}/presigned-url', [PropertyMediaController::class, 'presignedUrl'])
        ->whereIn('type', ['image', 'video']);
    Route::post('/properties/{property}/{type}', [PropertyMediaController::class, 'attach'])
        ->whereIn('type', ['image', 'video']);
    Route::delete('/properties/{property}/{type}/{mediaId}', [PropertyMediaController::class, 'destroy'])
        ->whereIn('type', ['image', 'video'])->whereNumber('mediaId');

    // Local/dev fallback for the pre-signed upload itself (see MediaUploadService).
    Route::post('/properties/{property}/{type}/direct-upload/{path}', [PropertyMediaController::class, 'directUpload'])
        ->whereIn('type', ['image', 'video'])
        ->where('path', '.*')
        ->name('properties.media.direct-upload');
});

// Sprint 3 — Monetization Engine (see 06-monetization-engine.md)
Route::get('/coupons', [CouponController::class, 'index']);

// Server-to-server only — verified by AdMobSsvVerifier's signature check,
// not by Sanctum. Never called by the app itself.
Route::get('/video-ads/ssv-callback', [VideoAdController::class, 'ssvCallback']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);

    Route::post('/unlocks/check', [UnlockController::class, 'check']);
    Route::post('/unlocks/spend', [UnlockController::class, 'spend'])->middleware('throttle:unlock-spend');

    Route::post('/video-ads/request-token', [VideoAdController::class, 'requestToken']);

    Route::post('/coupons/purchase', [CouponController::class, 'purchase']);
    Route::post('/coupons/redeem', [CouponController::class, 'redeem']);

    Route::get('/scratch-cards/pending', [ScratchCardController::class, 'pending']);
    Route::post('/scratch-cards/{scratchCard}/scratch', [ScratchCardController::class, 'scratch']);
});

// Sprint 4 — Chat & Notifications
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/chats', [ChatController::class, 'index']);
    Route::post('/chats', [ChatController::class, 'store']);
    Route::get('/chats/{chat}/messages', [ChatController::class, 'messages']);
    Route::post('/chats/{chat}/messages', [ChatController::class, 'sendMessage']);
    Route::post('/chats/{chat}/read', [ChatController::class, 'markRead']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/me/fcm-token', [NotificationController::class, 'registerFcmToken']);
});

// Sprint 5 — Admin APIs + public CMS (feeds the app home screen + website)
Route::get('/banners', [CmsController::class, 'banners']);
Route::get('/blogs', [CmsController::class, 'blogs']);
Route::get('/blogs/{slug}', [CmsController::class, 'blog']);
Route::get('/faqs', [CmsController::class, 'faqs']);

// Per doc01: moderators handle day-to-day moderation (listings, reports,
// KYC); coupon/reward config, CMS, analytics, and user management are
// admin-only. admin.php itself splits into two role-gated sub-groups.
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin|moderator'])
    ->group(base_path('routes/admin.php'));
