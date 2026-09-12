<?php

namespace Tests\Feature;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PropertySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_keyword_search_matches_title(): void
    {
        Property::factory()->create(['title' => 'Sunny Lakeview Apartment', 'status' => 'live']);
        Property::factory()->create(['title' => 'Downtown Studio', 'status' => 'live']);

        $response = $this->getJson('/api/v1/properties?q=Lakeview');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('Sunny Lakeview Apartment', $response->json('data.0.title'));
    }

    public function test_keyword_search_combines_with_structured_filters(): void
    {
        $matching = Property::factory()->create(['title' => 'Garden Villa', 'status' => 'live', 'price' => 1000000]);
        Property::factory()->create(['title' => 'Garden House', 'status' => 'live', 'price' => 9000000]);

        $response = $this->getJson('/api/v1/properties?q=Garden&max_price=2000000');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($matching->id, $response->json('data.0.id'));
    }

    public function test_non_live_properties_are_never_returned_by_search(): void
    {
        Property::factory()->pendingReview()->create(['title' => 'Hidden Gem Apartment']);

        $response = $this->getJson('/api/v1/properties?q=Hidden');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_featured_listings_are_cached_between_reads_with_no_writes(): void
    {
        Property::factory()->featured()->create();

        $this->getJson('/api/v1/properties/featured')->assertOk()->assertJsonCount(1, 'data');

        // Insert a featured row directly (bypassing the observer) to prove
        // the second read comes from cache, not a fresh query.
        DB::table('properties')->insert(Property::factory()->featured()->make()->toArray());

        $this->getJson('/api/v1/properties/featured')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_any_property_write_invalidates_the_featured_cache(): void
    {
        Property::factory()->featured()->create();
        $this->getJson('/api/v1/properties/featured')->assertOk()->assertJsonCount(1, 'data');

        Property::factory()->featured()->create();

        $this->getJson('/api/v1/properties/featured')->assertOk()->assertJsonCount(2, 'data');
    }
}
