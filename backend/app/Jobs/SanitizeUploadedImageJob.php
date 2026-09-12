<?php

namespace App\Jobs;

use App\Services\Media\ImageSanitizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Queued rather than inline: with a real S3 pre-signed upload, the bytes
 * never pass through this API's request body at all — the only place
 * left to sanitize them is a post-upload pass over what's already
 * stored, which is exactly what this does regardless of local vs S3.
 */
class SanitizeUploadedImageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $disk,
        public string $path,
    ) {}

    public function handle(ImageSanitizer $sanitizer): void
    {
        $contents = Storage::disk($this->disk)->get($this->path);

        if ($contents === null) {
            return;
        }

        $sanitized = $sanitizer->sanitize($contents);

        if ($sanitized !== null) {
            Storage::disk($this->disk)->put($this->path, $sanitized);
        }
    }
}
