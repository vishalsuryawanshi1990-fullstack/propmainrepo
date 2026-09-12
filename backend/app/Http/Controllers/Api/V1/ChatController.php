<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ChatResource;
use App\Models\Chat;
use App\Models\ContactUnlock;
use App\Models\Property;
use App\Services\Chat\PhoneNumberMasker;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $chats = Chat::where('buyer_id', $userId)
            ->orWhere('seller_id', $userId)
            ->with(['buyer', 'seller'])
            ->latest('last_message_at')
            ->get()
            ->each(function (Chat $chat) use ($userId) {
                $chat->unread_count = $chat->messages()
                    ->where('sender_id', '!=', $userId)
                    ->whereNull('read_at')
                    ->count();
            });

        return response()->apiSuccess(ChatResource::collection($chats));
    }

    /**
     * Finds or starts the buyer<->owner chat for a property — doc04 only
     * documents GET /chats and the message endpoints, but something has
     * to create the chat the first time a buyer taps "Message Owner".
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate(['property_id' => ['required', 'integer', 'exists:properties,id']]);
        $property = Property::findOrFail($request->integer('property_id'));
        $buyer = $request->user();

        abort_if($property->owner_id === $buyer->id, 422, 'You cannot start a chat about your own listing.');

        $chat = Chat::firstOrCreate([
            'property_id' => $property->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $property->owner_id,
        ]);

        return response()->apiSuccess(new ChatResource($chat->load(['buyer', 'seller'])), 'OK', [], $chat->wasRecentlyCreated ? 201 : 200);
    }

    public function messages(Request $request, Chat $chat): JsonResponse
    {
        $this->assertParticipant($request, $chat);

        $messages = $chat->messages()->latest()->paginate(30);

        return response()->apiSuccess(ChatMessageResource::collection($messages));
    }

    public function sendMessage(Request $request, Chat $chat, PhoneNumberMasker $masker, NotificationService $notifications): JsonResponse
    {
        $this->assertParticipant($request, $chat);
        $request->validate(['message' => ['required_without:attachment_path', 'nullable', 'string', 'max:2000']]);

        $contactAlreadyUnlocked = ContactUnlock::where('unlocker_user_id', $chat->buyer_id)
            ->where('property_id', $chat->property_id)
            ->exists();

        $body = $request->string('message')->toString();

        if ($body !== '' && ! $contactAlreadyUnlocked) {
            $body = $masker->mask($body);
        }

        $message = $chat->messages()->create([
            'sender_id' => $request->user()->id,
            'message' => $body,
            'attachment_path' => $request->input('attachment_path'),
        ]);

        $chat->update(['last_message_at' => now()]);

        broadcast(new MessageSent($message))->toOthers();

        $recipient = $request->user()->id === $chat->buyer_id ? $chat->seller : $chat->buyer;
        $notifications->notify($recipient, 'chat.message', 'New message', $body !== '' ? $body : 'Sent an attachment', ['chat_id' => $chat->id]);

        return response()->apiSuccess(new ChatMessageResource($message), 'Sent.', [], 201);
    }

    public function markRead(Request $request, Chat $chat): JsonResponse
    {
        $this->assertParticipant($request, $chat);

        $chat->messages()->where('sender_id', '!=', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->apiSuccess(null, 'Marked as read.');
    }

    private function assertParticipant(Request $request, Chat $chat): void
    {
        abort_unless(in_array($request->user()->id, [$chat->buyer_id, $chat->seller_id], true), 403);
    }
}
