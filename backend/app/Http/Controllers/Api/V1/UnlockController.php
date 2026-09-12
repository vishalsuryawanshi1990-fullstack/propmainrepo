<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnlockPropertyRequest;
use App\Models\ContactUnlock;
use App\Models\Property;
use App\Services\Monetization\InsufficientCreditsException;
use App\Services\Monetization\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * 06-monetization-engine.md's unlock flow. Note: this only implements the
 * buyer-unlocks-owner direction the contact_unlocks table actually models
 * (unlocker_user_id, property_id) — the reverse case doc06 also mentions
 * ("a seller wanting a buyer's number who enquired") needs its own
 * per-conversation ledger and is deferred to the chat feature.
 */
class UnlockController extends Controller
{
    public function check(UnlockPropertyRequest $request): JsonResponse
    {
        $property = Property::findOrFail($request->integer('property_id'));
        $user = $request->user();

        $alreadyUnlocked = ContactUnlock::where('unlocker_user_id', $user->id)
            ->where('property_id', $property->id)
            ->exists();

        if ($alreadyUnlocked) {
            return response()->apiSuccess(['already_unlocked' => true, 'contact' => $this->contactFor($property)]);
        }

        return response()->apiSuccess([
            'already_unlocked' => false,
            'can_unlock_directly' => $user->wallet->contact_unlock_credits >= 1,
        ]);
    }

    public function spend(UnlockPropertyRequest $request, WalletService $wallets): JsonResponse
    {
        $property = Property::findOrFail($request->integer('property_id'));
        $user = $request->user();

        abort_if($property->owner_id === $user->id, 422, 'You already own this listing.');

        $existing = ContactUnlock::where('unlocker_user_id', $user->id)->where('property_id', $property->id)->first();

        if ($existing) {
            return response()->apiSuccess(['contact' => $this->contactFor($property)], 'Already unlocked.');
        }

        try {
            DB::transaction(function () use ($user, $property, $wallets) {
                $wallets->debit($user->wallet, 1, 'unlock_spend', $property->id);

                ContactUnlock::create([
                    'unlocker_user_id' => $user->id,
                    'property_id' => $property->id,
                    'credits_spent' => 1,
                    'unlocked_at' => now(),
                ]);
            });
        } catch (InsufficientCreditsException $e) {
            return response()->apiError($e->getMessage(), [], 422);
        }

        return response()->apiSuccess(['contact' => $this->contactFor($property)], 'Unlocked.');
    }

    private function contactFor(Property $property): array
    {
        $owner = $property->owner;

        return ['name' => $owner->name, 'phone' => $owner->phone, 'email' => $owner->email];
    }
}
