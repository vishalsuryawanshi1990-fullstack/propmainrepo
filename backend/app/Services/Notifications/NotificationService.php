<?php

namespace App\Services\Notifications;

use App\Models\AppNotification;
use App\Models\User;

/**
 * Every notification lands in the in-app notification center (doc08
 * Sprint 6) first — the FCM push is best-effort on top of that, never a
 * replacement for it, since push delivery isn't guaranteed.
 */
class NotificationService
{
    public function __construct(private readonly FcmPushService $fcm) {}

    public function notify(User $user, string $type, string $title, string $body, array $data = []): AppNotification
    {
        $notification = AppNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        if ($user->fcm_token) {
            $this->fcm->send($user->fcm_token, $title, $body, ['type' => $type, ...$data]);
        }

        return $notification;
    }
}
