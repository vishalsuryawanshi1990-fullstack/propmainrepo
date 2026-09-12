<?php

namespace Database\Factories;

use App\Models\PropertyTypeMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyTypeMaster>
 */
class PropertyTypeMasterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Apartment', 'Villa', 'Plot', 'Commercial', 'PG/Co-living']),
        ];
    }
}
