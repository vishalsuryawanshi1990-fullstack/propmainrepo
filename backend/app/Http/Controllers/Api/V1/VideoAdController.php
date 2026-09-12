<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VideoAdEvent;
use App\Services\Monetization\AdMobSsvVerifier;
use App\Services\Monetization\ScratchCardService;
use App\Services\Monetization\WalletService;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VideoAdController extends Controller
{
    /**
     * Ties the eventual ad watch to this user/session — the SSV callback
     * later matches on this token, never on anything the client reports.
     */
    public function requestToken(Request $request): JsonResponse
    {
        $user = $request->user();

        $watchedToday = VideoAdEvent::where('user_id', $user->id)
            ->where('credited', true)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($watchedToday >= (int) config('monetization.max_daily_video_watches')) {
            return response()->apiError('Daily video-watch limit reached.', [], 429);
        }

        $lastWatch = VideoAdEvent::where('user_id', $user->id)->latest()->first();

        if ($lastWatch && $lastWatch->created_at->diffInSeconds(now()) < (int) config('monetization.video_watch_cooldown_seconds')) {
            return response()->apiError('Please wait before watching another ad.', [], 429);
        }

        $event = VideoAdEvent::create([
            'user_id' => $user->id,
            'request_token' => (string) Str::uuid(),
        ]);

        return response()->apiSuccess(['request_token' => $event->request_token]);
    }

    /**
     * Called by AdMob's servers only — never by the app. The request_token
     * from requestToken() above is round-tripped as AdMob's `custom_data`
     * param, which is how we match this callback back to a specific user
     * and prevent a client from ever self-reporting "ad watched".
     *
     * Always return 200 (even on failure) — Google retries on non-2xx,
     * and we don't want a flood of retries for a request we've already
     * decided is invalid/replayed.
     */
    public function ssvCallback(Request $request, AdMobSsvVerifier $verifier, WalletService $wallets, ScratchCardService $scratchCards, NotificationService $notifications): JsonResponse
    {
        if (! $verifier->verify($request)) {
            Log::warning('AdMob SSV signature verification failed', ['query' => $request->query()]);

            return response()->apiSuccess(null, 'Ignored.');
        }

        $requestToken = $request->query('custom_data');
        $event = VideoAdEvent::where('request_token', $requestToken)->first();

        if (! $event || $event->credited) {
            return response()->apiSuccess(null, 'Ignored.');
        }

        $event->forceFill(['ssv_verified' => true, 'credited' => true])->save();

        $user = User::find($event->user_id);
        $wallets->credit($user->wallet, 1, 'video_ad', $event->id);
        $scratchCards->spawn($user, 'video');
        $notifications->notify($user, 'scratch_card.ready', 'Scratch card ready!', 'Scratch your card to reveal your reward.');

        return response()->apiSuccess(null, 'Credited.');
    }
}
