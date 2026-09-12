<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_verified' => $this->phone_verified_at !== null,
            'roles' => $this->getRoleNames(),
            'status' => $this->status,
            'wallet_credits' => $this->whenLoaded('wallet', fn () => $this->wallet?->contact_unlock_credits),
            'created_at' => $this->created_at,
        ];
    }
}
