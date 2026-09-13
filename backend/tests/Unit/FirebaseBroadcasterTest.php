<?php

namespace Tests\Unit;

use App\Broadcasting\FirebaseBroadcaster;
use App\Services\Google\GoogleServiceAccount;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FirebaseBroadcasterTest extends TestCase
{
    public function test_broadcast_writes_the_payload_to_the_channels_path_stripped_of_its_pusher_prefix(): void
    {
        $google = $this->createMock(GoogleServiceAccount::class);
        $google->method('accessToken')->willReturn('fake-admin-token');

        Http::fake(['*' => Http::response(null, 200)]);

        $broadcaster = new FirebaseBroadcaster($google, 'https://fake-project-default-rtdb.firebaseio.com');
        $broadcaster->broadcast([new PrivateChannel('chat.5')], 'message.sent', ['message' => 'hi']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://fake-project-default-rtdb.firebaseio.com/chat.5/messages.json'
                && $request->hasHeader('Authorization', 'Bearer fake-admin-token')
                && $request['message'] === 'hi'
                && $request['event'] === 'message.sent';
        });
    }

    public function test_broadcast_throws_when_no_service_account_is_configured(): void
    {
        $google = $this->createMock(GoogleServiceAccount::class);
        $google->method('accessToken')->willReturn(null);

        $broadcaster = new FirebaseBroadcaster($google, 'https://fake-project-default-rtdb.firebaseio.com');

        $this->expectException(BroadcastException::class);

        $broadcaster->broadcast([new Channel('chat.5')], 'message.sent');
    }
}
