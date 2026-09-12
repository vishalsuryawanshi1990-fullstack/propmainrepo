<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\User;
use App\Services\Monetization\WalletService;
use App\Services\Payments\RazorpayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeRazorpayGateway;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(RazorpayGateway::class, FakeRazorpayGateway::class);
    }

    public function test_index_only_lists_active_unexpired_coupons(): void
    {
        Coupon::factory()->create(['is_active' => true, 'valid_until' => null]);
        Coupon::factory()->create(['is_active' => false]);
        Coupon::factory()->create(['is_active' => true, 'valid_until' => now()->subDay()]);

        $response = $this->getJson('/api/v1/coupons');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_purchase_creates_a_razorpay_order_and_a_pending_payment(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::factory()->create(['price' => 19, 'value' => 3, 'type' => 'fixed_credits']);

        $response = $this->actingAs($user)->postJson('/api/v1/coupons/purchase', ['coupon_id' => $coupon->id]);

        $response->assertStatus(201);
        $this->assertNotEmpty($response->json('data.order_id'));
        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'purpose' => 'coupon_purchase',
            'status' => 'created',
            'amount' => 19,
        ]);

        // Never credited from the purchase call itself — only the webhook does that.
        app(WalletService::class)->createForNewUser($user);
        $this->assertSame(1, $user->wallet->fresh()->contact_unlock_credits);
    }

    public function test_purchasing_an_inactive_coupon_fails(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::factory()->create(['is_active' => false]);

        $this->actingAs($user)->postJson('/api/v1/coupons/purchase', ['coupon_id' => $coupon->id])
            ->assertStatus(422);
    }

    public function test_redeeming_a_fixed_credit_promo_code_credits_the_wallet_immediately(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->createForNewUser($user)->update(['contact_unlock_credits' => 0]);
        $coupon = Coupon::factory()->create(['code' => 'WELCOME5', 'type' => 'fixed_credits', 'value' => 5]);

        $response = $this->actingAs($user)->postJson('/api/v1/coupons/redeem', ['code' => 'WELCOME5']);

        $response->assertOk();
        $this->assertSame(5, $user->wallet->fresh()->contact_unlock_credits);
        $this->assertSame(1, $coupon->fresh()->redemptions_count);
    }

    public function test_redeeming_a_percentage_discount_coupon_is_rejected(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::factory()->create(['code' => 'DISC10', 'type' => 'percentage_discount', 'value' => 10]);

        $this->actingAs($user)->postJson('/api/v1/coupons/redeem', ['code' => 'DISC10'])
            ->assertStatus(422);
    }
}
