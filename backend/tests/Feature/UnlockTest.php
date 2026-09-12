<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use App\Services\Monetization\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_reports_can_unlock_directly_when_credits_available(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->createForNewUser($user);
        $property = Property::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/unlocks/check', ['property_id' => $property->id]);

        $response->assertOk()
            ->assertJsonPath('data.already_unlocked', false)
            ->assertJsonPath('data.can_unlock_directly', true);
    }

    public function test_spend_consumes_a_credit_and_reveals_contact(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->createForNewUser($user);
        $property = Property::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/unlocks/spend', ['property_id' => $property->id]);

        $response->assertOk()->assertJsonPath('data.contact.phone', $property->owner->phone);
        $this->assertSame(0, $user->wallet->fresh()->contact_unlock_credits);
        $this->assertDatabaseHas('contact_unlocks', ['unlocker_user_id' => $user->id, 'property_id' => $property->id]);
    }

    public function test_spend_without_credits_fails(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->createForNewUser($user)->update(['contact_unlock_credits' => 0]);
        $property = Property::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/unlocks/spend', ['property_id' => $property->id]);

        $response->assertStatus(422);
    }

    public function test_spending_twice_does_not_double_charge(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->createForNewUser($user)->update(['contact_unlock_credits' => 5]);
        $property = Property::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/unlocks/spend', ['property_id' => $property->id]);
        $this->actingAs($user)->postJson('/api/v1/unlocks/spend', ['property_id' => $property->id])->assertOk();

        $this->assertSame(4, $user->wallet->fresh()->contact_unlock_credits);
        // signup_bonus + a single unlock_spend debit — the second call must not add another.
        $this->assertSame(2, $user->wallet->fresh()->transactions()->count());
    }

    public function test_cannot_unlock_your_own_listing(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->createForNewUser($user);
        $property = Property::factory()->create(['owner_id' => $user->id]);

        $this->actingAs($user)->postJson('/api/v1/unlocks/spend', ['property_id' => $property->id])
            ->assertStatus(422);
    }
}
