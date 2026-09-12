<?php

namespace Database\Factories;

use App\Models\CityMaster;
use App\Models\LocalityMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocalityMaster>
 */
class LocalityMasterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'city_id' => CityMaster::factory(),
            'name' => fake()->unique()->streetName(),
            'lat' => fake()->latitude(),
            'long' => fake()->longitude(),
            'avg_price_sqft' => fake()->randomFloat(2, 2000, 25000),
        ];
    }
}
