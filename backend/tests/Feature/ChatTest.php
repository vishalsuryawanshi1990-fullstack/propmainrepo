<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\ContactUnlock;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_buyer_can_start_a_chat_about_a_property(): void
    {
        $buyer = User::factory()->create();
        $property = Property::factory()->create();

        $response = $this->actingAs($buyer)->postJson('/api/v1/chats', ['property_id' => $property->id]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('chats', [
            'property_id' => $property->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $property->owner_id,
        ]);
    }

    public function test_starting_the_same_chat_twice_reuses_it(): void
    {
        $buyer = User::factory()->create();
        $property = Property::factory()->create();

        $first = $this->actingAs($buyer)->postJson('/api/v1/chats', ['property_id' => $property->id]);
        $second = $this->actingAs($buyer)->postJson('/api/v1/chats', ['property_id' => $property->id]);

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(1, Chat::count());
    }

    public function test_cannot_start_a_chat_on_your_own_listing(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)->postJson('/api/v1/chats', ['property_id' => $property->id])
            ->assertStatus(422);
    }

    public function test_a_phone_number_in_a_message_is_masked_when_contact_is_not_unlocked(): void
    {
        Event::fake([MessageSent::class]);
        $buyer = User::factory()->create();
        $property = Property::factory()->create();
        $chat = Chat::create(['property_id' => $property->id, 'buyer_id' => $buyer->id, 'seller_id' => $property->owner_id]);

        $response = $this->actingAs($buyer)->postJson("/api/v1/chats/{$chat->id}/messages", [
            'message' => 'Call me at 9876543210 please',
        ]);

        $response->assertStatus(201);
        $this->assertStringNotContainsString('9876543210', $response->json('data.message'));
        $this->assertStringContainsString('[phone number hidden]', $response->json('data.message'));
    }

    public function test_a_phone_number_is_not_masked_once_contact_is_unlocked(): void
    {
        Event::fake([MessageSent::class]);
        $buyer = User::factory()->create();
        $property = Property::factory()->create();
        $chat = Chat::create(['property_id' => $property->id, 'buyer_id' => $buyer->id, 'seller_id' => $property->owner_id]);
        ContactUnlock::create([
            'unlocker_user_id' => $buyer->id,
            'property_id' => $property->id,
            'credits_spent' => 1,
            'unlocked_at' => now(),
        ]);

        $response = $this->actingAs($buyer)->postJson("/api/v1/chats/{$chat->id}/messages", [
            'message' => 'Call me at 9876543210 please',
        ]);

        $this->assertStringContainsString('9876543210', $response->json('data.message'));
    }

    public function test_a_stranger_cannot_read_or_send_messages_in_someone_elses_chat(): void
    {
        $buyer = User::factory()->create();
        $property = Property::factory()->create();
        $chat = Chat::create(['property_id' => $property->id, 'buyer_id' => $buyer->id, 'seller_id' => $property->owner_id]);
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->getJson("/api/v1/chats/{$chat->id}/messages")->assertStatus(403);
        $this->actingAs($stranger)->postJson("/api/v1/chats/{$chat->id}/messages", ['message' => 'hi'])->assertStatus(403);
    }

    public function test_marking_a_chat_read_marks_the_other_partys_messages_read(): void
    {
        Event::fake([MessageSent::class]);
        $buyer = User::factory()->create();
        $property = Property::factory()->create();
        $seller = $property->owner;
        $chat = Chat::create(['property_id' => $property->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id]);

        $this->actingAs($buyer)->postJson("/api/v1/chats/{$chat->id}/messages", ['message' => 'Hello']);
        $message = $chat->messages()->first();
        $this->assertNull($message->read_at);

        $this->actingAs($seller)->postJson("/api/v1/chats/{$chat->id}/read")->assertOk();

        $this->assertNotNull($message->fresh()->read_at);
    }
}
