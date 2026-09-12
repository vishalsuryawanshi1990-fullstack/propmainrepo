<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Doc 02/07: "avoid proxying large files through the API server" — uploads
 * go straight from the client to S3 via a pre-signed URL. Locally (or in
 * tests) there is no S3, so the same contract falls back to a same-shaped
 * "direct upload" endpoint on this API instead of failing outright.
 */
class MediaUploadService
{
    public function disk(): string
    {
        return config('filesystems.default') === 's3' ? 's3' : 'public';
    }

    public function usesRealPresignedUrls(): bool
    {
        return $this->disk() === 's3';
    }

    public function generateKey(int $propertyId, string $type, string $extension): string
    {
        return "properties/{$propertyId}/{$type}s/".Str::uuid().'.'.$extension;
    }

    /**
     * @return array{upload_url: string, method: string, path: string}
     */
    public function presign(int $propertyId, string $type, string $extension, string $contentType): array
    {
        $path = $this->generateKey($propertyId, $type, $extension);

        if ($this->usesRealPresignedUrls()) {
            $upload = Storage::disk('s3')->temporaryUploadUrl(
                $path,
                now()->addMinutes(10),
                ['ContentType' => $contentType],
            );

            return ['upload_url' => $upload['url'], 'method' => 'PUT', 'path' => $path];
        }

        return [
            'upload_url' => route('properties.media.direct-upload', ['property' => $propertyId, 'type' => $type, 'path' => $path]),
            'method' => 'POST',
            'path' => $path,
        ];
    }
}
