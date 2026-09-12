<?php

use App\Http\Controllers\Api\V1\Admin\AnalyticsController;
use App\Http\Controllers\Api\V1\Admin\AuditLogController;
use App\Http\Controllers\Api\V1\Admin\BannerController;
use App\Http\Controllers\Api\V1\Admin\BlogController;
use App\Http\Controllers\Api\V1\Admin\CouponController;
use App\Http\Controllers\Api\V1\Admin\FaqController;
use App\Http\Controllers\Api\V1\Admin\KycReviewController;
use App\Http\Controllers\Api\V1\Admin\PropertyModerationController;
use App\Http\Controllers\Api\V1\Admin\ReportedListingController;
use App\Http\Controllers\Api\V1\Admin\ScratchRewardController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Base path /api/v1/admin — mounted with auth:sanctum + role:admin|moderator in api_v1.php.
// See 04-api-specification.md "Admin" section and 09-admin-panel-tasks.md.

// Moderator-level: day-to-day moderation (doc01's Moderator capabilities).
Route::get('/properties/pending', [PropertyModerationController::class, 'pending']);
Route::post('/properties/{property}/approve', [PropertyModerationController::class, 'approve']);
Route::post('/properties/{property}/reject', [PropertyModerationController::class, 'reject']);

Route::get('/reported-listings', [ReportedListingController::class, 'index']);
Route::post('/reported-listings/{reportedListing}/resolve', [ReportedListingController::class, 'resolve']);

Route::get('/kyc/pending', [KycReviewController::class, 'pending']);
Route::post('/kyc/{kycDocument}/verify', [KycReviewController::class, 'verify']);
Route::post('/kyc/{kycDocument}/reject', [KycReviewController::class, 'reject']);
Route::get('/kyc/{kycDocument}/file', [KycReviewController::class, 'download'])
    ->name('admin.kyc.download')
    ->middleware('signed');

// Admin-only: coupon/reward config, CMS, analytics, user management —
// doc01 reserves these for Super Admin, not day-to-day moderators.
Route::middleware('role:admin')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus']);

    Route::apiResource('coupons', CouponController::class)->except(['show']);
    Route::apiResource('scratch-rewards', ScratchRewardController::class)->except(['show'])
        ->parameters(['scratch-rewards' => 'scratchReward']);

    Route::get('/analytics/signups', [AnalyticsController::class, 'signups']);
    Route::get('/analytics/listings-by-status', [AnalyticsController::class, 'listingsByStatus']);
    Route::get('/analytics/revenue', [AnalyticsController::class, 'revenue']);
    Route::get('/analytics/unlock-funnel', [AnalyticsController::class, 'unlockFunnel']);

    Route::apiResource('cms/banners', BannerController::class)->except(['show'])->parameters(['cms/banners' => 'banner']);
    Route::apiResource('cms/blogs', BlogController::class)->except(['show'])->parameters(['cms/blogs' => 'blog']);
    Route::apiResource('cms/faqs', FaqController::class)->except(['show'])->parameters(['cms/faqs' => 'faq']);

    Route::get('/audit-logs', [AuditLogController::class, 'index']);
});
