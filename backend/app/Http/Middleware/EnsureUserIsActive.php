<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * doc09's "Suspend/ban user flow" needs to actually do something: a
 * suspended/banned user's existing token must stop working immediately,
 * not just at their next login.
 */
class EnsureUserIsActive
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Explicit guard: this runs in the global "api" middleware group,
        // before any route-specific auth:sanctum middleware has had a
        // chance to switch the default guard — $request->user() with no
        // argument would resolve against the app's default ("web")
        // guard here and always see a guest.
        $user = $request->user('sanctum');

        if ($user && $user->status !== 'active') {
            return response()->apiError("Your account is {$user->status}.", [], 403);
        }

        return $next($request);
    }
}
