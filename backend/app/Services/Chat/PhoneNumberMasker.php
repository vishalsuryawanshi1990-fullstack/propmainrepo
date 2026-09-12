<?php

namespace App\Services\Chat;

/**
 * 01-project-overview.md / 07-backend-tasks-laravel.md Sprint 4: "phone
 * number masking logic (don't leak real numbers into chat unless
 * explicitly unlocked)" — this is what stops a chat from being used to
 * route around the contact-unlock paywall.
 */
class PhoneNumberMasker
{
    /**
     * Matches sequences of 8+ digits, optionally separated by spaces,
     * dashes or dots — long enough to catch a phone number while
     * ignoring short numeric mentions ("2 bhk", "floor 3").
     */
    private const PHONE_PATTERN = '/(?:\d[\s.\-]?){8,}\d/';

    public function mask(string $message): string
    {
        return preg_replace(self::PHONE_PATTERN, '[phone number hidden]', $message);
    }

    public function containsPhoneNumber(string $message): bool
    {
        return preg_match(self::PHONE_PATTERN, $message) === 1;
    }
}
