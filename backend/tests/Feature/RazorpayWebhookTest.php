<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Payment;
use App\Models\User;
use App\Services\Monetization\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RazorpayWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.razorpay.webhook_secret' => 'test-webhook-secret']);
    }

    public function test_a_validly_signed_payment_captured_event_credits_the_wallet(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->createForNewUser($user)->update(['contact_unlock_credits' => 0]);
        $coupon = Coupon::factory()->create(['value' => 3, 'type' => 'fixed_credits']);
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'gateway_order_id' => 'order_abc123',
            'purpose' => 'coupon_purchase',
            'status' => 'created',
            'metadata' => ['coupon_id' => $coupon->id],
        ]);

        $body = json_encode([
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_xyz789', 'order_id' => 'order_abc123']]],
        ]);
        $signature = hash_hmac('sha256', $body, 'test-webhook-secret');

        $response = $this->call('POST', '/api/webhooks/razorpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_Razorpay_Signature' => $signature,
        ], $body);

        $response->assertOk();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame(3, $user->wallet->fresh()->contact_unlock_credits);
        $this->assertDatabaseHas('coupon_redemptions', ['coupon_id' => $coupon->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('scratch_cards', ['user_id' => $user->id, 'triggered_by' => 'coupon']);
    }

    public function test_an_invalid_signature_is_rejected_and_does_not_credit(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->createForNewUser($user)->update(['contact_unlock_credits' => 0]);
        $coupon = Coupon::factory()->create();
        Payment::factory()->create([
            'user_id' => $user->id,
            'gateway_order_id' => 'order_bad',
            'purpose' => 'coupon_purchase',
            'status' => 'created',
            'metadata' => ['coupon_id' => $coupon->id],
        ]);

        $body = json_encode(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => ['id' => 'pay_x', 'order_id' => 'order_bad']]]]);

        $response = $this->call('POST', '/api/webhooks/razorpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_Razorpay_Signature' => 'not-a-real-signature',
        ], $body);

        $response->assertStatus(400);
        $this->assertSame(0, $user->wallet->fresh()->contact_unlock_credits);
    }

    public function test_a_replayed_webhook_does_not_double_credit(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->createForNewUser($user)->update(['contact_unlock_credits' => 0]);
        $coupon = Coupon::factory()->create(['value' => 3, 'type' => 'fixed_credits']);
        Payment::factory()->create([
            'user_id' => $user->id,
            'gateway_order_id' => 'order_replay',
            'purpose' => 'coupon_purchase',
            'status' => 'created',
            'metadata' => ['coupon_id' => $coupon->id],
        ]);

        $body = json_encode(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => ['id' => 'pay_replay', 'order_id' => 'order_replay']]]]);
        $signature = hash_hmac('sha256', $body, 'test-webhook-secret');
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_Razorpay_Signature' => $signature];

        $this->call('POST', '/api/webhooks/razorpay', [], [], [], $headers, $body);
        $this->call('POST', '/api/webhooks/razorpay', [], [], [], $headers, $body);

        $this->assertSame(3, $user->wallet->fresh()->contact_unlock_credits);
    }
}
