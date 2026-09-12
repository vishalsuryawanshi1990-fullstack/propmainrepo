<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 05-security-compliance.md: "TLS 1.2+ enforced everywhere (HSTS
 * header)". Only sent in production — a local/staging box terminating
 * plain HTTP shouldn't tell browsers to force HTTPS on it.
 */
class EnforceHsts
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
