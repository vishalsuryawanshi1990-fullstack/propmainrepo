<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VideoAdEvent;
use App\Services\Monetization\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VideoAdTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_token_is_issued_and_stored(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/video-ads/request-token');

        $response->assertOk();
        $this->assertDatabaseHas('video_ad_events', [
            'request_token' => $response->json('data.request_token'),
            'credited' => false,
        ]);
    }

    public function test_daily_cap_blocks_further_requests(): void
    {
        $user = User::factory()->create();
        config(['monetization.max_daily_video_watches' => 1]);
        VideoAdEvent::create(['user_id' => $user->id, 'request_token' => 'seed-1', 'credited' => true]);

        $response = $this->actingAs($user)->postJson('/api/v1/video-ads/request-token');

        $response->assertStatus(429);
    }

    public function test_cooldown_blocks_rapid_repeat_requests(): void
    {
        $user = User::factory()->create();
        config(['monetization.video_watch_cooldown_seconds' => 120]);
        VideoAdEvent::create(['user_id' => $user->id, 'request_token' => 'seed-2', 'credited' => false]);

        $response = $this->actingAs($user)->postJson('/api/v1/video-ads/request-token');

        $response->assertStatus(429);
    }

    public function test_a_validly_signed_ssv_callback_credits_the_wallet_and_spawns_a_scratch_card(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->createForNewUser($user)->update(['contact_unlock_credits' => 0]);
        $event = VideoAdEvent::create(['user_id' => $user->id, 'request_token' => 'tok-123', 'credited' => false]);

        [$privateKey, $publicKeyPem] = $this->generateEcKeyPair();
        Http::fake([
            'gstatic*' => Http::response(['keys' => [['keyId' => 1, 'pem' => $publicKeyPem]]]),
        ]);

        $params = [
            'ad_network' => '1',
            'custom_data' => 'tok-123',
            'transaction_id' => 'txn-1',
            'key_id' => '1',
        ];
        $content = http_build_query($params);
        openssl_sign($content, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $signedUrl = '/api/v1/video-ads/ssv-callback?'.$content.'&signature='.$this->base64UrlEncode($signature);

        $response = $this->getJson($signedUrl);

        $response->assertOk();
        $this->assertTrue($event->fresh()->credited);
        $this->assertSame(1, $user->wallet->fresh()->contact_unlock_credits);
        $this->assertDatabaseHas('scratch_cards', ['user_id' => $user->id, 'triggered_by' => 'video']);
    }

    public function test_a_tampered_ssv_callback_does_not_credit(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->createForNewUser($user)->update(['contact_unlock_credits' => 0]);
        VideoAdEvent::create(['user_id' => $user->id, 'request_token' => 'tok-456', 'credited' => false]);

        [, $publicKeyPem] = $this->generateEcKeyPair();
        Http::fake(['gstatic*' => Http::response(['keys' => [['keyId' => 1, 'pem' => $publicKeyPem]]])]);

        $response = $this->getJson('/api/v1/video-ads/ssv-callback?custom_data=tok-456&key_id=1&signature=bm90LWEtcmVhbC1zaWduYXR1cmU');

        $response->assertOk(); // always 200 to Google, but...
        $this->assertSame(0, $user->wallet->fresh()->contact_unlock_credits); // ...never actually credited
    }

    private function generateEcKeyPair(): array
    {
        $keyPair = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        openssl_pkey_export($keyPair, $privateKeyPem);
        $publicKeyPem = openssl_pkey_get_details($keyPair)['key'];

        return [$privateKeyPem, $publicKeyPem];
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
