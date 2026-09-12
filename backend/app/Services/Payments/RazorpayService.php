<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\User;

/**
 * 05-security-compliance.md: never touch/store card data directly —
 * Razorpay Checkout handles entry, we only ever see order/payment ids.
 */
class RazorpayService
{
    public function __construct(private readonly RazorpayGateway $gateway) {}

    /**
     * Amount is always resolved server-side from the thing being
     * purchased (a coupon's price, a plan's price) — never accept a
     * client-supplied amount here.
     */
    public function createOrder(User $user, int $amountInRupees, string $purpose, array $metadata = []): Payment
    {
        $order = $this->gateway->createOrder(
            $amountInRupees * 100, // Razorpay wants paise
            'INR',
            ['user_id' => $user->id, 'purpose' => $purpose],
        );

        return Payment::create([
            'user_id' => $user->id,
            'gateway' => 'razorpay',
            'gateway_order_id' => $order['id'],
            'amount' => $amountInRupees,
            'purpose' => $purpose,
            'metadata' => $metadata,
            'status' => 'created',
        ]);
    }
}
