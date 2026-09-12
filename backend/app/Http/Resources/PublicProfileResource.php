<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Public page for an agent/seller — no phone/email here, contact stays
     * behind the unlock flow per 06-monetization-engine.md.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->agentProfile ?? $this->sellerProfile;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->getRoleNames()->first(),
            'business_name' => $profile?->business_name,
            'rera_id' => $profile?->rera_id,
            'bio' => $profile?->bio,
            'avatar_path' => $profile?->avatar_path,
            'is_verified_owner' => (bool) $profile?->is_verified_owner,
            'member_since' => $this->created_at,
        ];
    }
}
