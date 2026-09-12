<?php

use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// private-chat.{chat_id} per 04-api-specification.md — only the two
// participants may subscribe.
Broadcast::channel('chat.{chatId}', function ($user, int $chatId) {
    $chat = Chat::find($chatId);

    return $chat && ($user->id === $chat->buyer_id || $user->id === $chat->seller_id);
});
