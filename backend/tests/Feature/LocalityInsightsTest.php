<?php

namespace Tests\Feature;

use App\Models\LocalityMaster;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalityInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_insights_compute_avg_price_per_sqft_from_live_listings(): void
    {
        $locality = LocalityMaster::factory()->create(['avg_price_sqft' => 5000]);
        Property::factory()->create(['locality_id' => $locality->id, 'status' => 'live', 'price' => 1000000, 'area_sqft' => 1000]);
        Property::factory()->create(['locality_id' => $locality->id, 'status' => 'live', 'price' => 2000000, 'area_sqft' => 1000]);
        Property::factory()->pendingReview()->create(['locality_id' => $locality->id, 'price' => 100, 'area_sqft' => 1]);

        $response = $this->getJson("/api/v1/localities/{$locality->id}/insights");

        $response->assertOk();
        $this->assertEquals(1500.0, $response->json('data.avg_price_sqft'));
        $this->assertSame(2, $response->json('data.sample_size'));
    }

    public function test_insights_are_invalidated_when_a_property_changes(): void
    {
        $locality = LocalityMaster::factory()->create();
        $property = Property::factory()->create(['locality_id' => $locality->id, 'status' => 'live', 'price' => 1000000, 'area_sqft' => 1000]);

        $this->getJson("/api/v1/localities/{$locality->id}/insights")->assertOk();

        Property::factory()->create(['locality_id' => $locality->id, 'status' => 'live', 'price' => 5000000, 'area_sqft' => 1000]);
        $property->touch();

        $response = $this->getJson("/api/v1/localities/{$locality->id}/insights");
        $this->assertSame(2, $response->json('data.sample_size'));
    }
}
