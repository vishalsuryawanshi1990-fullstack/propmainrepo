<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Monetization\WalletService;
use App\Services\Otp\OtpService;
use App\Support\TokenTtl;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OtpController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    /**
     * Rate-limited hard at the route (5/hour/phone, 20/hour/IP) per
     * 04-api-specification.md — SMS pumping is the #1 abused endpoint on
     * every listings site.
     */
    public function request(RequestOtpRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->toString();

        if ($this->otp->isOnCooldown($phone)) {
            return response()->apiError('Please wait before requesting another code.', [], 429);
        }

        $this->otp->issue($phone);

        return response()->apiSuccess(null, 'OTP sent.');
    }

    public function verify(VerifyOtpRequest $request, WalletService $wallets): JsonResponse
    {
        $phone = $request->string('phone')->toString();

        if (! $this->otp->verify($phone, $request->string('otp')->toString())) {
            return response()->apiError('Invalid or expired OTP.', [], 422);
        }

        $isNewUser = ! User::where('phone', $phone)->exists();
        $deviceId = $request->string('device_id')->toString();

        if ($isNewUser && $deviceId !== '' && $this->deviceSignupLimitReached($deviceId)) {
            return response()->apiError('Too many accounts created from this device today.', [], 429);
        }

        /** @var User $user */
        $user = DB::transaction(function () use ($phone, $request, $isNewUser, $wallets) {
            $user = User::firstOrCreate(
                ['phone' => $phone],
                ['name' => 'New User', 'phone_verified_at' => now(), 'status' => 'active'],
            );

            if ($isNewUser) {
                $user->assignRole('buyer');
                $wallets->createForNewUser($user);
            }

            if ($user->phone_verified_at === null) {
                $user->forceFill(['phone_verified_at' => now()])->save();
            }

            $user->forceFill([
                'device_id' => $request->string('device_id')->toString() ?: $user->device_id,
                'last_login_at' => now(),
            ])->save();

            return $user;
        });

        $token = $user->createToken('mobile', ['*'], TokenTtl::for($user));

        return response()->apiSuccess([
            'token' => $token->plainTextToken,
            'is_new_user' => $isNewUser,
            'needs_registration' => $user->name === 'New User',
            'user' => new UserResource($user),
        ], 'OTP verified.');
    }

    /**
     * doc05: "Device fingerprinting + IP velocity checks on signup to
     * slow down fake-account farms created purely to harvest free
     * unlock credits."
     */
    private function deviceSignupLimitReached(string $deviceId): bool
    {
        return User::where('device_id', $deviceId)
            ->where('created_at', '>=', now()->subDay())
            ->count() >= (int) config('security.max_signups_per_device_per_day');
    }
}
