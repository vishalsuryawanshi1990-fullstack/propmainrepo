<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScratchRewardRequest;
use App\Http\Requests\Admin\UpdateScratchRewardRequest;
use App\Models\ScratchRewardMaster;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;

class ScratchRewardController extends Controller
{
    public function index(): JsonResponse
    {
        $rewards = ScratchRewardMaster::all();
        $totalWeight = $rewards->where('is_active', true)->sum('probability_weight');

        return response()->apiSuccess($rewards->map(fn ($reward) => [
            ...$reward->toArray(),
            'odds_percent' => $totalWeight > 0 && $reward->is_active
                ? round($reward->probability_weight / $totalWeight * 100, 2)
                : 0,
        ]));
    }

    public function store(StoreScratchRewardRequest $request): JsonResponse
    {
        $reward = ScratchRewardMaster::create($request->validated());
        AuditLogger::log('scratch-reward.create', $reward, null, $reward->toArray());

        return response()->apiSuccess($reward, 'Reward created.', [], 201);
    }

    public function update(UpdateScratchRewardRequest $request, ScratchRewardMaster $scratchReward): JsonResponse
    {
        $before = $scratchReward->toArray();
        $scratchReward->update($request->validated());
        AuditLogger::log('scratch-reward.update', $scratchReward, $before, $scratchReward->fresh()->toArray());

        return response()->apiSuccess($scratchReward, 'Reward updated.');
    }

    public function destroy(ScratchRewardMaster $scratchReward): JsonResponse
    {
        $before = $scratchReward->toArray();
        $scratchReward->delete();
        AuditLogger::log('scratch-reward.delete', $scratchReward, $before, null);

        return response()->apiSuccess(null, 'Reward deleted.');
    }
}
