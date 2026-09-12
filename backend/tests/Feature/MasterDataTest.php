<?php

namespace Tests\Feature;

use App\Models\CityMaster;
use App\Models\LocalityMaster;
use App\Models\PropertyTypeMaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_types_are_listed(): void
    {
        PropertyTypeMaster::factory()->create(['name' => 'Villa']);

        $this->getJson('/api/v1/property-types')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_cities_are_listed(): void
    {
        CityMaster::factory()->create();

        $this->getJson('/api/v1/cities')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_localities_are_filtered_by_city(): void
    {
        $cityA = CityMaster::factory()->create();
        $cityB = CityMaster::factory()->create();
        LocalityMaster::factory()->create(['city_id' => $cityA->id]);
        LocalityMaster::factory()->create(['city_id' => $cityB->id]);

        $response = $this->getJson("/api/v1/localities?city_id={$cityA->id}");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_localities_requires_a_valid_city_id(): void
    {
        $this->getJson('/api/v1/localities')->assertStatus(422);
    }
}
