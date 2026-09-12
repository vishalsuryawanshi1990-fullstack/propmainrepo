<?php

namespace App\Support;

use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Sanctum has no separate access/refresh token concept, so we approximate
 * 05-security-compliance.md's "shorter TTL for admin" requirement by
 * varying the expires_at we pass to createToken() by role.
 */
class TokenTtl
{
    public static function for(User $user): CarbonInterface
    {
        return $user->hasAnyRole(['admin', 'moderator'])
            ? now()->addHours(2)
            : now()->addDays(30);
    }
}
