<?php

namespace App\Broadcasting;

use App\Services\Google\GoogleServiceAccount;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\Broadcasters\UsePusherChannelConventions;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Realtime chat over Firebase Realtime Database instead of Pusher/Reverb —
 * shared hosting can't run a persistent WebSocket listener, and this reuses
 * the same Google service account already required for FCM push (see
 * FcmPushService) instead of adding a second third-party vendor.
 *
 * routes/channels.php authorization still runs exactly as it does for
 * Pusher (verifyUserCanAccessChannel(), unchanged). What differs is what a
 * successful private-channel auth hands the client: not a channel
 * signature, but a Firebase custom-auth token for that user, which the
 * frontend exchanges via signInWithCustomToken() so Firebase Security
 * Rules can independently enforce "only this chat's participants may read
 * chats/{chatId}/messages" on the client's own direct connection. This
 * class's own broadcast() writes use an admin-scoped OAuth2 token, which
 * bypasses those rules — the rules only gate client reads, never this
 * server's writes.
 */
class FirebaseBroadcaster extends Broadcaster
{
    use UsePusherChannelConventions;

    private const DATABASE_SCOPE = 'https://www.googleapis.com/auth/firebase.database https://www.googleapis.com/auth/userinfo.email';

    public function __construct(private GoogleServiceAccount $google, private string $databaseUrl) {}

    public function auth($request)
    {
        $channelName = $this->normalizeChannelName($request->channel_name);

        if (empty($request->channel_name) ||
            ($this->isGuardedChannel($request->channel_name) && ! $this->retrieveUser($request, $channelName))) {
            throw new AccessDeniedHttpException;
        }

        return parent::verifyUserCanAccessChannel($request, $channelName);
    }

    public function validAuthenticationResponse($request, $result)
    {
        $channelName = $this->normalizeChannelName($request->channel_name);
        $user = $this->retrieveUser($request, $channelName);
        $uid = 'user_'.$user->getAuthIdentifier();

        $token = $this->google->signCustomToken($uid);

        if ($token === null) {
            throw new BroadcastException('Firebase service account not configured — set FIREBASE_CREDENTIALS_PATH.');
        }

        return ['firebase_token' => $token, 'firebase_uid' => $uid];
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
        $accessToken = $this->google->accessToken(self::DATABASE_SCOPE);

        if ($accessToken === null) {
            throw new BroadcastException('Firebase service account not configured — set FIREBASE_CREDENTIALS_PATH.');
        }

        foreach ($this->formatChannels($channels) as $channel) {
            $path = $this->normalizeChannelName($channel);

            $response = Http::withToken($accessToken)->post(
                "{$this->databaseUrl}/{$path}/messages.json",
                array_merge($payload, ['event' => $event]),
            );

            if ($response->failed()) {
                throw new BroadcastException("Firebase Realtime Database write failed: {$response->body()}");
            }
        }
    }
}
