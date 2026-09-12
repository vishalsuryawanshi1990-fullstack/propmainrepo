<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Support\TokenTtl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Profile completion after OTP verify (name + role selection). Doc 04's
     * /auth/register runs on an already-authenticated (OTP-verified) user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'name' => $request->string('name')->toString(),
            'email' => $request->input('email'),
        ])->save();

        if (! $user->hasRole($request->string('role')->toString())) {
            $user->syncRoles([$request->string('role')->toString()]);
        }

        return response()->apiSuccess(new UserResource($user->fresh()), 'Registered.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->apiSuccess(null, 'Logged out.');
    }

    /**
     * Sanctum has no built-in refresh grant, so "refresh" here revokes the
     * presented token and issues a fresh one with a new TTL (sliding
     * expiration) — see App\Support\TokenTtl.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();

        $token = $user->createToken('mobile', ['*'], TokenTtl::for($user));

        return response()->apiSuccess(['token' => $token->plainTextToken], 'Token refreshed.');
    }
}
