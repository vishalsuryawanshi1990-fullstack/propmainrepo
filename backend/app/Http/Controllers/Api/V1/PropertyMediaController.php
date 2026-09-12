<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachMediaRequest;
use App\Http\Requests\PresignMediaRequest;
use App\Http\Resources\PropertyImageResource;
use App\Http\Resources\PropertyVideoResource;
use App\Jobs\DetectDuplicateListingJob;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyVideo;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Pre-signed-URL media flow per 02-tech-stack-architecture.md /
 * 07-backend-tasks-laravel.md Sprint 2: the client asks here for an
 * upload URL, PUTs/POSTs bytes straight to it (never through this API's
 * request body), then calls attach() to record the resulting file.
 */
class PropertyMediaController extends Controller
{
    public function presignedUrl(PresignMediaRequest $request, Property $property, string $type, MediaUploadService $media): JsonResponse
    {
        $this->assertValidType($type);

        $presigned = $media->presign(
            $property->id,
            $type,
            $request->string('extension')->toString(),
            $request->string('content_type')->toString(),
        );

        return response()->apiSuccess($presigned);
    }

    /**
     * Local/dev-only fallback used when there is no S3 disk configured —
     * see MediaUploadService::usesRealPresignedUrls(). Accepts the raw
     * file body at the exact key the presign step generated.
     */
    public function directUpload(Request $request, Property $property, string $type, string $path, MediaUploadService $media): JsonResponse
    {
        abort_if($media->usesRealPresignedUrls(), 404);
        $this->assertValidType($type);
        $this->authorize('update', $property);
        abort_unless(str_starts_with($path, "properties/{$property->id}/{$type}s/"), 422);

        Storage::disk($media->disk())->put($path, $request->getContent());

        return response()->apiSuccess(null, 'Uploaded.');
    }

    public function attach(AttachMediaRequest $request, Property $property, string $type): JsonResponse
    {
        $this->assertValidType($type);

        $model = $type === 'image' ? PropertyImage::class : PropertyVideo::class;
        $isPrimary = $request->boolean('is_primary');

        if ($isPrimary) {
            $property->{$type.'s'}()->update(['is_primary' => false]);
        }

        $record = $model::create([
            'property_id' => $property->id,
            'file_path' => $request->string('path')->toString(),
            'is_primary' => $isPrimary,
            'sort_order' => $request->integer('sort_order'),
        ]);

        if ($type === 'image') {
            DetectDuplicateListingJob::dispatch($property->id);
        }

        $resource = $type === 'image' ? PropertyImageResource::class : PropertyVideoResource::class;

        return response()->apiSuccess(new $resource($record), 'Attached.', [], 201);
    }

    public function destroy(Property $property, string $type, int $mediaId, MediaUploadService $media): JsonResponse
    {
        $this->assertValidType($type);
        $this->authorize('update', $property);

        $model = $type === 'image' ? PropertyImage::class : PropertyVideo::class;
        $record = $model::where('property_id', $property->id)->findOrFail($mediaId);

        Storage::disk($media->disk())->delete($record->file_path);
        $record->delete();

        return response()->apiSuccess(null, 'Removed.');
    }

    private function assertValidType(string $type): void
    {
        abort_unless(in_array($type, ['image', 'video'], true), 404);
    }
}
