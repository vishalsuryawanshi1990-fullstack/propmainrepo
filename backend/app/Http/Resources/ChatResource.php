<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $other = $viewer?->id === $this->buyer_id ? $this->seller : $this->buyer;

        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'other_party' => $other ? ['id' => $other->id, 'name' => $other->name] : null,
            'last_message_at' => $this->last_message_at,
            'unread_count' => $this->when(isset($this->unread_count), fn () => (int) $this->unread_count),
        ];
    }
}
