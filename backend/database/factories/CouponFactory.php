<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('COUP####')),
            'type' => 'fixed_credits',
            'value' => 3,
            'price' => 19,
            'max_redemptions' => null,
            'redemptions_count' => 0,
            'valid_from' => null,
            'valid_until' => null,
            'is_active' => true,
        ];
    }
}
