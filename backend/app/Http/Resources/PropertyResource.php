<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * Contact fields (owner phone/email) never appear here — they stay
     * behind the unlock flow per 06-monetization-engine.md.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'property_type' => $this->whenLoaded('propertyType', fn () => $this->propertyType->name),
            'listing_type' => $this->listing_type,
            'price' => (float) $this->price,
            'price_negotiable' => $this->price_negotiable,
            'area_sqft' => $this->area_sqft !== null ? (float) $this->area_sqft : null,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'floor_no' => $this->floor_no,
            'total_floors' => $this->total_floors,
            'furnishing_status' => $this->furnishing_status,
            'city_id' => $this->city_id,
            'city' => $this->whenLoaded('city', fn () => $this->city->name),
            'locality_id' => $this->locality_id,
            'locality' => $this->whenLoaded('locality', fn () => $this->locality?->name),
            'locality_text' => $this->locality_text,
            'address' => $this->address,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'distance_km' => $this->when(isset($this->distance_km), fn () => round((float) $this->distance_km, 2)),
            'rera_registration_no' => $this->rera_registration_no,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'views_count' => $this->views_count,
            'is_favorited' => $this->when(isset($this->is_favorited), fn () => (bool) $this->is_favorited),
            'images' => PropertyImageResource::collection($this->whenLoaded('images')),
            'videos' => PropertyVideoResource::collection($this->whenLoaded('videos')),
            'amenities' => $this->whenLoaded('amenities', fn () => $this->amenities->pluck('name')),
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'is_verified_owner' => (bool) ($this->owner->sellerProfile?->is_verified_owner ?? $this->owner->agentProfile?->is_verified_owner),
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
