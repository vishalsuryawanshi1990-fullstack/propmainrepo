<?php

namespace App\Services\Payments;

interface RazorpayGateway
{
    /**
     * @return array{id: string}
     */
    public function createOrder(int $amountInPaise, string $currency, array $notes): array;
}
