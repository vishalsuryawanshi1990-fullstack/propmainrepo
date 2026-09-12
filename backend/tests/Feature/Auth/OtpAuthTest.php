<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Otp\OtpGateway;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeOtpGateway;
use Tests\TestCase;

class OtpAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(OtpGateway::class, FakeOtpGateway::class);
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_requesting_an_otp_issues_one_to_the_gateway(): void
    {
        $response = $this->postJson('/api/v1/auth/otp/request', ['phone' => '9111111111']);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertArrayHasKey('9111111111', FakeOtpGateway::$sent);
    }

    public function test_verifying_a_wrong_otp_fails(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '9111111112']);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '9111111112',
            'otp' => '000000',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_verifying_a_correct_otp_creates_a_user_with_a_signup_credit(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '9111111113']);
        $otp = FakeOtpGateway::$sent['9111111113'];

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '9111111113',
            'otp' => $otp,
        ]);

        $response->assertOk()->assertJsonPath('data.is_new_user', true);

        $user = User::where('phone', '9111111113')->firstOrFail();
        $this->assertTrue($user->hasRole('buyer'));
        $this->assertSame(1, $user->wallet->contact_unlock_credits);
    }

    public function test_a_second_login_does_not_re_grant_the_signup_credit(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '9111111114']);
        $otp = FakeOtpGateway::$sent['9111111114'];
        $this->postJson('/api/v1/auth/otp/verify', ['phone' => '9111111114', 'otp' => $otp]);

        $this->travel(61)->seconds(); // past the resend cooldown
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '9111111114']);
        $otp = FakeOtpGateway::$sent['9111111114'];
        $response = $this->postJson('/api/v1/auth/otp/verify', ['phone' => '9111111114', 'otp' => $otp]);

        $response->assertOk()->assertJsonPath('data.is_new_user', false);

        $user = User::where('phone', '9111111114')->firstOrFail();
        $this->assertSame(1, $user->wallet->contact_unlock_credits);
    }

    public function test_register_completes_the_profile_and_sets_role(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '9111111115']);
        $otp = FakeOtpGateway::$sent['9111111115'];
        $verify = $this->postJson('/api/v1/auth/otp/verify', ['phone' => '9111111115', 'otp' => $otp]);
        $token = $verify->json('data.token');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/register', ['name' => 'Jane Seller', 'role' => 'seller']);

        $response->assertOk()->assertJsonPath('data.name', 'Jane Seller');

        $user = User::where('phone', '9111111115')->firstOrFail();
        $this->assertTrue($user->hasRole('seller'));
        $this->assertFalse($user->hasRole('buyer'));
    }

    public function test_me_requires_authentication_and_returns_json_not_a_redirect(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401)->assertJson(['success' => false]);
    }

    /**
     * Regression: without an explicit Accept header, Laravel's default
     * Authenticate::redirectTo() tries route('login') and blows up with a
     * 500 in an API-only app that has no such route. bootstrap/app.php's
     * redirectGuestsTo(null) must keep this a clean 401.
     */
    public function test_me_without_json_accept_header_is_still_a_clean_401(): void
    {
        $response = $this->get('/api/v1/me');

        $response->assertStatus(401);
    }
}
