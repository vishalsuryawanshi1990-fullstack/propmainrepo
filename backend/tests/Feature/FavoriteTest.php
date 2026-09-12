<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_favorite_and_unfavorite_a_property(): void
    {
        $user = User::factory()->create();
        $property = Property::factory()->create();

        $this->actingAs($user)->postJson("/api/v1/properties/{$property->id}/favorite")->assertStatus(201);
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'property_id' => $property->id]);

        $this->actingAs($user)->getJson('/api/v1/favorites')->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($user)->deleteJson("/api/v1/properties/{$property->id}/favorite")->assertOk();
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'property_id' => $property->id]);
    }

    public function test_favoriting_the_same_property_twice_is_idempotent(): void
    {
        $user = User::factory()->create();
        $property = Property::factory()->create();

        $this->actingAs($user)->postJson("/api/v1/properties/{$property->id}/favorite");
        $this->actingAs($user)->postJson("/api/v1/properties/{$property->id}/favorite");

        $this->assertSame(1, $user->favorites()->count());
    }
}
