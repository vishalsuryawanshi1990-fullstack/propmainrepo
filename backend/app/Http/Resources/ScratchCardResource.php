<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScratchCardResource extends JsonResource
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
            'triggered_by' => $this->triggered_by,
            'is_scratched' => $this->is_scratched,
            'expires_at' => $this->expires_at,
        ];
    }
}
