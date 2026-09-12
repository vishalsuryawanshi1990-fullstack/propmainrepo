<?php

namespace Database\Factories;

use App\Models\CityMaster;
use App\Models\LocalityMaster;
use App\Models\Property;
use App\Models\PropertyTypeMaster;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'property_type_id' => PropertyTypeMaster::factory(),
            'listing_type' => fake()->randomElement(['sale', 'rent']),
            'price' => fake()->randomFloat(2, 500000, 20000000),
            'price_negotiable' => fake()->boolean(),
            'area_sqft' => fake()->randomFloat(2, 300, 5000),
            'bedrooms' => fake()->numberBetween(1, 5),
            'bathrooms' => fake()->numberBetween(1, 4),
            'floor_no' => fake()->numberBetween(0, 20),
            'total_floors' => fake()->numberBetween(1, 25),
            'furnishing_status' => fake()->randomElement(['unfurnished', 'semi-furnished', 'furnished']),
            'city_id' => CityMaster::factory(),
            'locality_id' => LocalityMaster::factory(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(12.8, 13.2),
            'longitude' => fake()->longitude(77.4, 77.8),
            'status' => 'live',
            'is_featured' => false,
        ];
    }

    public function pendingReview(): static
    {
        return $this->state(['status' => 'pending_review']);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
