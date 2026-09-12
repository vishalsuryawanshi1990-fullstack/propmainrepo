<?php

namespace Database\Factories;

use App\Models\CityMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CityMaster>
 */
class CityMasterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'state' => fake()->state(),
        ];
    }
}
