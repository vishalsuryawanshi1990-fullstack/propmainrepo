<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * Every admin mutation gets a row here per 05-security-compliance.md
 * ("Audit log for every admin action: approve/reject listing, KYC decision,
 * coupon/reward-odds changes, manual wallet adjustments").
 */
class AuditLogger
{
    public static function log(string $action, Model $subject, ?array $before, ?array $after): AuditLog
    {
        return AuditLog::create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'before' => $before,
            'after' => $after,
            'ip_address' => Request::ip(),
        ]);
    }
}
