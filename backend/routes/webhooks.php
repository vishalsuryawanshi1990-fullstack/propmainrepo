<?php

use App\Http\Controllers\Api\Webhooks\RazorpayWebhookController;
use Illuminate\Support\Facades\Route;

// Base path /api/webhooks — deliberately outside auth:sanctum and CSRF (Laravel's
// "api" middleware group has no CSRF anyway). Every handler here MUST verify its own
// signature per 05-security-compliance.md — never trust an unsigned payload.

Route::post('/razorpay', [RazorpayWebhookController::class, 'handle']);
