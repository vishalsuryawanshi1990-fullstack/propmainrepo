<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Monetization\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_shows_the_signup_bonus(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->createForNewUser($user);

        $response = $this->actingAs($user)->getJson('/api/v1/wallet');

        $response->assertOk()->assertJsonPath('data.contact_unlock_credits', 1);
    }

    public function test_wallet_transactions_are_listed(): void
    {
        $user = User::factory()->create();
        $wallet = app(WalletService::class)->createForNewUser($user);
        app(WalletService::class)->credit($wallet, 3, 'coupon');

        $response = $this->actingAs($user)->getJson('/api/v1/wallet/transactions');

        $response->assertOk()->assertJsonCount(2, 'data');
    }
}
