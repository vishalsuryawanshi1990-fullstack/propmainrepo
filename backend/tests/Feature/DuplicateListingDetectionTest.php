<?php

namespace Tests\Feature;

use App\Jobs\DetectDuplicateListingJob;
use App\Models\Property;
use App\Services\Media\MediaUploadService;
use App\Services\Media\PerceptualHash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DuplicateListingDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_same_address_and_price_flags_a_duplicate(): void
    {
        $first = Property::factory()->create(['address' => '42 Palm Grove Road', 'price' => 5000000]);
        $second = Property::factory()->create(['address' => '  42   PALM grove road  ', 'price' => 5000000]);

        (new DetectDuplicateListingJob($first->id))->handle(app(PerceptualHash::class), app(MediaUploadService::class));
        (new DetectDuplicateListingJob($second->id))->handle(app(PerceptualHash::class), app(MediaUploadService::class));

        $second->refresh();
        $this->assertTrue($second->is_flagged_duplicate);
        $this->assertSame($first->id, $second->duplicate_of_property_id);
    }

    public function test_different_address_and_price_is_not_flagged(): void
    {
        Property::factory()->create(['address' => '1 First St', 'price' => 1000000]);
        $second = Property::factory()->create(['address' => '2 Second St', 'price' => 2000000]);

        (new DetectDuplicateListingJob($second->id))->handle(app(PerceptualHash::class), app(MediaUploadService::class));

        $this->assertFalse($second->fresh()->is_flagged_duplicate);
    }

    public function test_identical_primary_images_flag_a_duplicate_even_with_different_addresses(): void
    {
        $imageBytes = $this->makeTestImage();

        $first = Property::factory()->create(['address' => 'Address A', 'price' => 1000000]);
        $first->images()->create(['file_path' => 'properties/'.$first->id.'/images/a.png', 'is_primary' => true]);
        Storage::disk('public')->put('properties/'.$first->id.'/images/a.png', $imageBytes);

        $second = Property::factory()->create(['address' => 'Address B', 'price' => 2000000]);
        $second->images()->create(['file_path' => 'properties/'.$second->id.'/images/b.png', 'is_primary' => true]);
        Storage::disk('public')->put('properties/'.$second->id.'/images/b.png', $imageBytes);

        (new DetectDuplicateListingJob($first->id))->handle(app(PerceptualHash::class), app(MediaUploadService::class));
        (new DetectDuplicateListingJob($second->id))->handle(app(PerceptualHash::class), app(MediaUploadService::class));

        $this->assertTrue($second->fresh()->is_flagged_duplicate);
    }

    private function makeTestImage(): string
    {
        $image = imagecreatetruecolor(20, 20);
        imagefill($image, 0, 0, imagecolorallocate($image, 120, 40, 200));
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }
}
