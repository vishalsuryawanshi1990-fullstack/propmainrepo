<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'gateway' => 'razorpay',
            'gateway_order_id' => 'order_'.fake()->unique()->bothify('??########'),
            'amount' => fake()->randomFloat(2, 10, 500),
            'purpose' => 'coupon_purchase',
            'status' => 'created',
            'webhook_verified' => false,
        ];
    }
}
