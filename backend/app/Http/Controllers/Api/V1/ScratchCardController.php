<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScratchCardResource;
use App\Models\ScratchCard;
use App\Services\Monetization\ScratchCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScratchCardController extends Controller
{
    public function pending(Request $request): JsonResponse
    {
        $cards = $request->user()->scratchCards()
            ->where('is_scratched', false)
            ->where('expires_at', '>', now())
            ->get();

        return response()->apiSuccess(ScratchCardResource::collection($cards));
    }

    /**
     * The server rolls the weighted-random reward at this exact moment
     * (never earlier) per 06-monetization-engine.md, to prevent
     * reward-prediction/replay attacks.
     */
    public function scratch(Request $request, ScratchCard $scratchCard, ScratchCardService $scratchCards): JsonResponse
    {
        abort_unless($scratchCard->user_id === $request->user()->id, 403);
        abort_if($scratchCard->is_scratched, 422, 'This scratch card has already been scratched.');
        abort_if($scratchCard->expires_at->isPast(), 422, 'This scratch card has expired.');

        $result = $scratchCards->scratch($scratchCard, $request->user()->wallet);

        return response()->apiSuccess($result);
    }
}
