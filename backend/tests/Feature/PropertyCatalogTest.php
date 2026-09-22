<?php

namespace Tests\Feature;

use App\Models\CityMaster;
use App\Models\LocalityMaster;
use App\Models\Property;
use App\Models\PropertyTypeMaster;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class PropertyCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_only_live_properties_are_publicly_listed(): void
    {
        Property::factory()->create(['status' => 'live']);
        Property::factory()->pendingReview()->create();

        $response = $this->getJson('/api/v1/properties');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_search_filters_by_city_and_price_range(): void
    {
        $city = CityMaster::factory()->create();
        $otherCity = CityMaster::factory()->create();

        Property::factory()->create(['city_id' => $city->id, 'price' => 1000000, 'status' => 'live']);
        Property::factory()->create(['city_id' => $city->id, 'price' => 9000000, 'status' => 'live']);
        Property::factory()->create(['city_id' => $otherCity->id, 'price' => 1000000, 'status' => 'live']);

        $response = $this->getJson('/api/v1/properties?'.http_build_query([
            'city' => $city->id,
            'max_price' => 2000000,
        ]));

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_radius_search_excludes_properties_outside_the_radius(): void
    {
        // Bengaluru-ish center.
        Property::factory()->create(['latitude' => 12.9716, 'longitude' => 77.5946, 'status' => 'live']);
        // Roughly 400km away (Chennai) — well outside a 25km radius.
        Property::factory()->create(['latitude' => 13.0827, 'longitude' => 80.2707, 'status' => 'live']);

        $response = $this->getJson('/api/v1/properties?'.http_build_query([
            'lat' => 12.9716,
            'lng' => 77.5946,
            'radius_km' => 25,
        ]));

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_buyer_cannot_create_a_property(): void
    {
        $buyer = User::factory()->create();
        $buyer->assignRole('buyer');

        $response = $this->actingAs($buyer)->postJson('/api/v1/properties', $this->validPayload());

        $response->assertStatus(403);
    }

    public function test_a_seller_can_create_a_property_which_starts_pending_review(): void
    {
        Bus::fake();

        $seller = User::factory()->create();
        $seller->assignRole('seller');

        $response = $this->actingAs($seller)->postJson('/api/v1/properties', $this->validPayload());

        $response->assertStatus(201)->assertJsonPath('data.status', 'pending_review');
        $this->assertDatabaseHas(Property::class, ['owner_id' => $seller->id, 'status' => 'pending_review']);
    }

    public function test_a_pending_property_is_not_visible_to_the_public_but_is_to_its_owner(): void
    {
        $seller = User::factory()->create();
        $seller->assignRole('seller');
        $property = Property::factory()->pendingReview()->create(['owner_id' => $seller->id]);

        $this->getJson("/api/v1/properties/{$property->id}")->assertStatus(404);
        $this->actingAs($seller)->getJson("/api/v1/properties/{$property->id}")->assertOk();
    }

    public function test_only_the_owner_or_admin_can_update_a_property(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('seller');
        $stranger = User::factory()->create();
        $stranger->assignRole('seller');
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($stranger)->patchJson("/api/v1/properties/{$property->id}", ['title' => 'Hacked'])
            ->assertStatus(403);

        $this->actingAs($owner)->patchJson("/api/v1/properties/{$property->id}", ['title' => 'Updated Title'])
            ->assertOk()->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_owner_can_pause_and_reactivate_but_not_jump_straight_to_live_from_pending(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('seller');
        $property = Property::factory()->create(['owner_id' => $owner->id, 'status' => 'live']);

        $this->actingAs($owner)->patchJson("/api/v1/properties/{$property->id}/status", ['status' => 'paused'])
            ->assertOk()->assertJsonPath('data.status', 'paused');

        $this->actingAs($owner)->patchJson("/api/v1/properties/{$property->id}/status", ['status' => 'live'])
            ->assertOk()->assertJsonPath('data.status', 'live');

        $pending = Property::factory()->pendingReview()->create(['owner_id' => $owner->id]);
        $this->actingAs($owner)->patchJson("/api/v1/properties/{$pending->id}/status", ['status' => 'live'])
            ->assertStatus(422);
    }

    public function test_viewing_a_property_increments_view_count_once_per_window(): void
    {
        $property = Property::factory()->create();

        $this->getJson("/api/v1/properties/{$property->id}");
        $this->getJson("/api/v1/properties/{$property->id}");

        $this->assertSame(1, $property->fresh()->views_count);
    }

    public function test_similar_and_featured_endpoints_work(): void
    {
        $type = PropertyTypeMaster::factory()->create();
        $city = CityMaster::factory()->create();
        $property = Property::factory()->create(['property_type_id' => $type->id, 'city_id' => $city->id]);
        Property::factory()->create(['property_type_id' => $type->id, 'city_id' => $city->id]);
        Property::factory()->featured()->create();

        $this->getJson("/api/v1/properties/{$property->id}/similar")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/properties/featured')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_featured_endpoint_preserves_latest_first_order_across_a_cache_hit(): void
    {
        $older = Property::factory()->featured()->create(['created_at' => now()->subDay()]);
        $newer = Property::factory()->featured()->create(['created_at' => now()]);

        // First call populates the cache (ids only — see PropertyController::featured
        // docblock), second call re-queries by those cached ids and must
        // still come back newest-first, not database/whereIn order.
        $this->getJson('/api/v1/properties/featured')->assertOk();
        $response = $this->getJson('/api/v1/properties/featured');

        $response->assertOk();
        $this->assertSame([$newer->id, $older->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_a_property_can_be_created_with_free_text_locality_instead_of_a_locality_id(): void
    {
        Bus::fake();

        $seller = User::factory()->create();
        $seller->assignRole('seller');

        $payload = $this->validPayload();
        unset($payload['locality_id']);
        $payload['locality_text'] = 'Some Neighborhood';

        $response = $this->actingAs($seller)->postJson('/api/v1/properties', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.locality_id', null)
            ->assertJsonPath('data.locality_text', 'Some Neighborhood');
    }

    public function test_a_property_requires_either_locality_id_or_locality_text(): void
    {
        $seller = User::factory()->create();
        $seller->assignRole('seller');

        $payload = $this->validPayload();
        unset($payload['locality_id']);

        $this->actingAs($seller)->postJson('/api/v1/properties', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['locality_id', 'locality_text']);
    }

    private function validPayload(): array
    {
        $type = PropertyTypeMaster::factory()->create();
        $city = CityMaster::factory()->create();
        $locality = LocalityMaster::factory()->create(['city_id' => $city->id]);

        return [
            'title' => 'Spacious 2BHK',
            'description' => 'Nice place.',
            'property_type_id' => $type->id,
            'listing_type' => 'rent',
            'price' => 25000,
            'city_id' => $city->id,
            'locality_id' => $locality->id,
            'address' => '123 Test Street',
            'latitude' => 12.9716,
            'longitude' => 77.5946,
        ];
    }
}
