<?php

namespace App\Services\Payments;

use Razorpay\Api\Api;

class RazorpayApiGateway implements RazorpayGateway
{
    public function __construct(private readonly Api $api) {}

    public function createOrder(int $amountInPaise, string $currency, array $notes): array
    {
        return $this->api->order->create([
            'amount' => $amountInPaise,
            'currency' => $currency,
            'notes' => $notes,
        ])->toArray();
    }
}
