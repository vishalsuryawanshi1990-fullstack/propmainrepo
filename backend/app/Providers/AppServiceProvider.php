<?php

namespace App\Providers;

use App\Broadcasting\FirebaseBroadcaster;
use App\Services\Google\GoogleServiceAccount;
use App\Services\Otp\Gateways\LogOtpGateway;
use App\Services\Otp\Gateways\Msg91OtpGateway;
use App\Services\Otp\Gateways\TwilioOtpGateway;
use App\Services\Otp\OtpGateway;
use App\Services\Payments\RazorpayApiGateway;
use App\Services\Payments\RazorpayGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Razorpay\Api\Api as RazorpayApi;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OtpGateway::class, function () {
            return match (config('otp.gateway')) {
                'msg91' => new Msg91OtpGateway,
                'twilio' => new TwilioOtpGateway,
                default => new LogOtpGateway,
            };
        });

        $this->app->singleton(RazorpayApi::class, function () {
            return new RazorpayApi(config('services.razorpay.key'), config('services.razorpay.secret'));
        });

        $this->app->bind(RazorpayGateway::class, RazorpayApiGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Shared-hosting realtime chat transport — see FirebaseBroadcaster.
        Broadcast::extend('firebase', function ($app, array $config) {
            return new FirebaseBroadcaster($app->make(GoogleServiceAccount::class), rtrim($config['database_url'] ?? '', '/'));
        });

        Response::macro('apiSuccess', function (mixed $data = null, string $message = 'OK', array $meta = [], int $status = 200): JsonResponse {
            $payload = ['success' => true, 'data' => $data, 'message' => $message];

            if ($meta !== []) {
                $payload['meta'] = $meta;
            }

            return Response::json($payload, $status);
        });

        Response::macro('apiError', function (string $message = 'Error', array $errors = [], int $status = 400): JsonResponse {
            $payload = ['success' => false, 'message' => $message];

            if ($errors !== []) {
                $payload['errors'] = $errors;
            }

            return Response::json($payload, $status);
        });

        // A raw paginator passed straight to apiSuccess() serializes with
        // its own {data, links, meta, current_page, ...} wrapper nested
        // under our "data" key — this flattens it to the same {data,
        // meta:{page,per_page,total}} shape every other list endpoint
        // uses. (A paginator wrapped in a JsonResource::collection() does
        // NOT have this problem — only pass raw paginators here.)
        Response::macro('apiPaginated', function (LengthAwarePaginator $paginator, ?callable $map = null, string $message = 'OK'): JsonResponse {
            $items = $map ? $paginator->getCollection()->map($map) : $paginator->getCollection();

            return Response::apiSuccess(
                $items,
                $message,
                ['page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()],
            );
        });

        // Baseline limits per 04-api-specification.md's rate-limiting table.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('otp-request', function (Request $request) {
            return [
                Limit::perHour(5)->by('otp-phone:'.$request->input('phone')),
                Limit::perHour(20)->by('otp-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('unlock-spend', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
