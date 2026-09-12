<?php

namespace Database\Factories;

use App\Models\AmenityMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AmenityMaster>
 */
class AmenityMasterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'icon' => 'icon-'.fake()->word(),
            'category' => fake()->randomElement(['society', 'flat', 'security']),
        ];
    }
}
