<?php

namespace App\Services\Monetization;

use App\Models\ScratchCard;
use App\Models\ScratchRewardMaster;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

/**
 * 06-monetization-engine.md: the reward is picked "at this moment (not
 * pre-determined at creation, to prevent reward-prediction/replay
 * attacks)" — spawn() only reserves a card; scratch() below is what
 * actually rolls the weighted-random reward.
 */
class ScratchCardService
{
    public function __construct(private readonly WalletService $wallets) {}

    public function spawn(User $user, string $triggeredBy): ScratchCard
    {
        return ScratchCard::create([
            'user_id' => $user->id,
            'triggered_by' => $triggeredBy,
            'expires_at' => now()->addHours((int) config('monetization.scratch_card_validity_hours')),
        ]);
    }

    /**
     * @return array{reward_type: string, value: float}
     */
    public function scratch(ScratchCard $scratchCard, Wallet $wallet): array
    {
        return DB::transaction(function () use ($scratchCard, $wallet) {
            $reward = $this->pickWeightedReward();

            $scratchCard->forceFill([
                'is_scratched' => true,
                'scratched_at' => now(),
                'reward_id' => $reward?->id,
            ])->save();

            if ($reward === null || $reward->reward_type === 'none') {
                return ['reward_type' => 'none', 'value' => 0];
            }

            match ($reward->reward_type) {
                'bonus_credit' => $this->wallets->credit($wallet, (int) $reward->value, 'scratch_reward', $scratchCard->id),
                'cashback' => $this->wallets->creditCashback($wallet, (float) $reward->value, 'scratch_reward', $scratchCard->id),
                // Non-cash discount vouchers need their own redemption
                // ledger to apply against a future coupon purchase — out
                // of scope for the MVP cut (doc11), recorded but not
                // fulfilled yet.
                'discount_voucher' => null,
                default => null,
            };

            return ['reward_type' => $reward->reward_type, 'value' => (float) $reward->value];
        });
    }

    private function pickWeightedReward(): ?ScratchRewardMaster
    {
        $rewards = ScratchRewardMaster::where('is_active', true)->get();
        $totalWeight = $rewards->sum('probability_weight');

        if ($totalWeight <= 0) {
            return null;
        }

        $roll = random_int(1, $totalWeight);
        $cumulative = 0;

        foreach ($rewards as $reward) {
            $cumulative += $reward->probability_weight;

            if ($roll <= $cumulative) {
                return $reward;
            }
        }

        return null;
    }
}
