<?php

namespace App\Jobs;

use App\Models\Property;
use App\Services\Media\MediaUploadService;
use App\Services\Media\PerceptualHash;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * 05-security-compliance.md: "Duplicate-listing detection: hash of
 * (address + price + image perceptual hash) to catch the same flat
 * re-posted by multiple 'owners' (classic broker-spam pattern)."
 *
 * Queued on listing creation per 07-backend-tasks-laravel.md Sprint 2, so
 * it never blocks the post-property request.
 */
class DetectDuplicateListingJob implements ShouldQueue
{
    use Queueable;

    /** Hamming distance below which two image hashes count as "the same photo". */
    private const IMAGE_DISTANCE_THRESHOLD = 10;

    public function __construct(public int $propertyId) {}

    public function handle(PerceptualHash $hasher, MediaUploadService $media): void
    {
        $property = Property::with('images')->find($this->propertyId);

        if (! $property) {
            return;
        }

        $property->address_price_hash = $this->addressPriceHash($property);

        $primaryImage = $property->images->firstWhere('is_primary', true) ?? $property->images->first();
        $property->primary_image_hash = $primaryImage
            ? $hasher->hashContents(Storage::disk($media->disk())->get($primaryImage->file_path))
            : null;

        $duplicate = $this->findDuplicate($property, $hasher);

        $property->duplicate_of_property_id = $duplicate?->id;
        $property->is_flagged_duplicate = $duplicate !== null;
        $property->save();
    }

    private function addressPriceHash(Property $property): string
    {
        $normalizedAddress = strtolower(preg_replace('/\s+/', ' ', trim($property->address)));

        return hash('sha256', $normalizedAddress.'|'.$property->price);
    }

    private function findDuplicate(Property $property, PerceptualHash $hasher): ?Property
    {
        $candidates = Property::query()
            ->where('id', '!=', $property->id)
            ->where('address_price_hash', $property->address_price_hash)
            ->whereNotNull('address_price_hash')
            ->get();

        if ($candidates->isNotEmpty()) {
            return $candidates->first();
        }

        if ($property->primary_image_hash === null) {
            return null;
        }

        return Property::query()
            ->where('id', '!=', $property->id)
            ->whereNotNull('primary_image_hash')
            ->get()
            ->first(fn (Property $candidate) => $hasher->distance($candidate->primary_image_hash, $property->primary_image_hash) <= self::IMAGE_DISTANCE_THRESHOLD);
    }
}
