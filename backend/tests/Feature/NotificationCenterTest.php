<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_are_listed_newest_first(): void
    {
        $user = User::factory()->create();
        $service = app(NotificationService::class);
        $service->notify($user, 'test.one', 'First', 'body');
        $service->notify($user, 'test.two', 'Second', 'body');

        $response = $this->actingAs($user)->getJson('/api/v1/notifications');

        $response->assertOk()->assertJsonPath('data.0.title', 'Second');
    }

    public function test_marking_one_notification_read(): void
    {
        $user = User::factory()->create();
        $notification = app(NotificationService::class)->notify($user, 'test', 'Title', 'body');

        $this->actingAs($user)->postJson("/api/v1/notifications/{$notification->id}/read")->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_marking_all_read(): void
    {
        $user = User::factory()->create();
        $service = app(NotificationService::class);
        $service->notify($user, 'a', 'A', 'body');
        $service->notify($user, 'b', 'B', 'body');

        $this->actingAs($user)->postJson('/api/v1/notifications/read-all')->assertOk();

        $this->assertSame(0, $user->notifications()->whereNull('read_at')->count());
    }

    public function test_a_user_cannot_mark_someone_elses_notification_read(): void
    {
        $owner = User::factory()->create();
        $notification = app(NotificationService::class)->notify($owner, 'test', 'Title', 'body');
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->postJson("/api/v1/notifications/{$notification->id}/read")->assertOk();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_registering_an_fcm_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/me/fcm-token', ['fcm_token' => 'a-device-token'])
            ->assertOk();

        $this->assertSame('a-device-token', $user->fresh()->fcm_token);
    }
}
