<?php

use App\Http\Controllers\Api\V1\Admin\CouponController;
use App\Http\Controllers\Api\V1\Admin\KycReviewController;
use App\Http\Controllers\Api\V1\Admin\ScratchRewardController;
use Illuminate\Support\Facades\Route;

// Base path /api/v1/admin — mounted with auth:sanctum + role:admin|moderator in api_v1.php.
// See 04-api-specification.md "Admin" section and 09-admin-panel-tasks.md.

// Route::get('/properties/pending', ...);
// Route::post('/properties/{property}/approve', ...);
// Route::post('/properties/{property}/reject', ...);
// Route::get('/users', ...);

Route::get('/kyc/pending', [KycReviewController::class, 'pending']);
Route::post('/kyc/{kycDocument}/verify', [KycReviewController::class, 'verify']);
Route::post('/kyc/{kycDocument}/reject', [KycReviewController::class, 'reject']);
Route::get('/kyc/{kycDocument}/file', [KycReviewController::class, 'download'])
    ->name('admin.kyc.download')
    ->middleware('signed');

Route::apiResource('coupons', CouponController::class)->except(['show']);
Route::apiResource('scratch-rewards', ScratchRewardController::class)->except(['show'])
    ->parameters(['scratch-rewards' => 'scratchReward']);
// Route::get('/analytics/{report}', ...);
// Route::apiResource('cms/banners', ...);
// Route::apiResource('cms/blogs', ...);
// Route::apiResource('cms/faqs', ...);
// Route::get('/audit-logs', ...);
