<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class KycDocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * The raw file path never leaves the server — moderators get a signed,
     * short-lived download link instead (05-security-compliance.md: KYC
     * document access must be controllable/loggable, not a public path).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isReviewer = $request->user()?->hasAnyRole(['admin', 'moderator']) ?? false;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'doc_type' => $this->doc_type,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'verified_at' => $this->verified_at,
            'created_at' => $this->created_at,
            'download_url' => $isReviewer
                ? URL::temporarySignedRoute('admin.kyc.download', now()->addMinutes(10), ['kycDocument' => $this->id])
                : null,
        ];
    }
}
