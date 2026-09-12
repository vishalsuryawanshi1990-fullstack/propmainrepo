<?php

namespace Tests\Feature;

use App\Models\ScratchRewardMaster;
use App\Models\User;
use App\Services\Monetization\ScratchCardService;
use App\Services\Monetization\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScratchCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_scratching_a_deterministic_bonus_credit_reward_credits_the_wallet(): void
    {
        ScratchRewardMaster::create(['reward_type' => 'bonus_credit', 'value' => 2, 'probability_weight' => 1, 'is_active' => true]);

        $user = User::factory()->create();
        $wallet = app(WalletService::class)->createForNewUser($user);
        $wallet->update(['contact_unlock_credits' => 0]);
        $card = app(ScratchCardService::class)->spawn($user, 'video');

        $response = $this->actingAs($user)->postJson("/api/v1/scratch-cards/{$card->id}/scratch");

        $response->assertOk()->assertJsonPath('data.reward_type', 'bonus_credit');
        $this->assertSame(2, $wallet->fresh()->contact_unlock_credits);
        $this->assertTrue($card->fresh()->is_scratched);
    }

    public function test_scratching_a_deterministic_cashback_reward_credits_cashback_balance(): void
    {
        ScratchRewardMaster::create(['reward_type' => 'cashback', 'value' => 15.5, 'probability_weight' => 1, 'is_active' => true]);

        $user = User::factory()->create();
        $wallet = app(WalletService::class)->createForNewUser($user);
        $card = app(ScratchCardService::class)->spawn($user, 'coupon');

        $this->actingAs($user)->postJson("/api/v1/scratch-cards/{$card->id}/scratch")->assertOk();

        $this->assertSame('15.50', $wallet->fresh()->cashback_balance);
    }

    public function test_no_active_rewards_yields_a_clean_no_win_result(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->createForNewUser($user);
        $card = app(ScratchCardService::class)->spawn($user, 'video');

        $response = $this->actingAs($user)->postJson("/api/v1/scratch-cards/{$card->id}/scratch");

        $response->assertOk()->assertJsonPath('data.reward_type', 'none');
    }

    public function test_cannot_scratch_twice(): void
    {
        ScratchRewardMaster::create(['reward_type' => 'bonus_credit', 'value' => 1, 'probability_weight' => 1, 'is_active' => true]);
        $user = User::factory()->create();
        app(WalletService::class)->createForNewUser($user);
        $card = app(ScratchCardService::class)->spawn($user, 'video');

        $this->actingAs($user)->postJson("/api/v1/scratch-cards/{$card->id}/scratch")->assertOk();
        $this->actingAs($user)->postJson("/api/v1/scratch-cards/{$card->id}/scratch")->assertStatus(422);
    }

    public function test_cannot_scratch_an_expired_card(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->createForNewUser($user);
        $card = app(ScratchCardService::class)->spawn($user, 'video');
        $card->update(['expires_at' => now()->subHour()]);

        $this->actingAs($user)->postJson("/api/v1/scratch-cards/{$card->id}/scratch")->assertStatus(422);
    }

    public function test_cannot_scratch_someone_elses_card(): void
    {
        $owner = User::factory()->create();
        app(WalletService::class)->createForNewUser($owner);
        $card = app(ScratchCardService::class)->spawn($owner, 'video');

        $stranger = User::factory()->create();
        app(WalletService::class)->createForNewUser($stranger);

        $this->actingAs($stranger)->postJson("/api/v1/scratch-cards/{$card->id}/scratch")->assertStatus(403);
    }

    public function test_pending_lists_only_unscratched_unexpired_cards(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->createForNewUser($user);
        $service = app(ScratchCardService::class);
        $service->spawn($user, 'video');
        $expired = $service->spawn($user, 'video');
        $expired->update(['expires_at' => now()->subHour()]);

        $response = $this->actingAs($user)->getJson('/api/v1/scratch-cards/pending');

        $response->assertOk()->assertJsonCount(1, 'data');
    }
}
