<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactUnlock;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function signups(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);

        $rows = User::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->apiSuccess($rows);
    }

    public function listingsByStatus(): JsonResponse
    {
        $rows = Property::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return response()->apiSuccess($rows);
    }

    /**
     * Revenue only exists for sources with a real payment (coupons) —
     * video ads generate ad revenue on Google's side, not a Payment row
     * here, so they're reported separately as a watch count, not ₹.
     */
    public function revenue(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);

        $byPurpose = Payment::query()
            ->where('status', 'paid')
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('purpose, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('purpose')
            ->get();

        $videoWatches = WalletTransaction::where('source', 'video_ad')->count();

        return response()->apiSuccess([
            'by_purpose' => $byPurpose,
            'video_ad_credits_granted' => $videoWatches,
        ]);
    }

    /**
     * signups -> free-unlock-used -> video/coupon top-up -> repeat, per
     * 09-admin-panel-tasks.md's dashboard funnel chart.
     */
    public function unlockFunnel(): JsonResponse
    {
        $totalSignups = User::count();
        $usedAnUnlock = ContactUnlock::distinct('unlocker_user_id')->count('unlocker_user_id');
        $toppedUpViaVideo = WalletTransaction::where('source', 'video_ad')->distinct('wallet_id')->count('wallet_id');
        $toppedUpViaCoupon = WalletTransaction::where('source', 'coupon')->distinct('wallet_id')->count('wallet_id');

        $repeatUnlockUsers = ContactUnlock::query()
            ->select('unlocker_user_id')
            ->groupBy('unlocker_user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        return response()->apiSuccess([
            'signups' => $totalSignups,
            'used_free_unlock' => $usedAnUnlock,
            'topped_up_via_video' => $toppedUpViaVideo,
            'topped_up_via_coupon' => $toppedUpViaCoupon,
            'repeat_unlockers' => $repeatUnlockUsers,
        ]);
    }
}
