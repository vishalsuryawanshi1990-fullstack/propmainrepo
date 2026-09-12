<?php

namespace Tests\Fakes;

use App\Services\Payments\RazorpayGateway;
use Illuminate\Support\Str;

class FakeRazorpayGateway implements RazorpayGateway
{
    /** @var array<int, array> */
    public static array $createdOrders = [];

    public function createOrder(int $amountInPaise, string $currency, array $notes): array
    {
        $order = ['id' => 'order_'.Str::random(14), 'amount' => $amountInPaise, 'currency' => $currency, 'notes' => $notes];
        static::$createdOrders[] = $order;

        return $order;
    }
}
