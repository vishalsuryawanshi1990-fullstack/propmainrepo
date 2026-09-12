<?php

namespace App\Providers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
    }
}
