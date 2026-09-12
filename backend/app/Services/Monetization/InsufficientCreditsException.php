<?php

namespace App\Services\Monetization;

use RuntimeException;

class InsufficientCreditsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Wallet does not have enough contact-unlock credits.');
    }
}
