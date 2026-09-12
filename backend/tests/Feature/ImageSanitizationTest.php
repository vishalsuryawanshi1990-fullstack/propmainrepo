<?php

namespace Tests\Feature;

use App\Jobs\SanitizeUploadedImageJob;
use App\Services\Media\ImageSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageSanitizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sanitizer_re_encodes_a_real_image(): void
    {
        $original = $this->makeJpegWithExif();

        $sanitized = (new ImageSanitizer)->sanitize($original);

        $this->assertNotNull($sanitized);
        $this->assertNotSame($original, $sanitized);
        // Re-encoded output is a valid JPEG in its own right.
        $this->assertNotFalse(@imagecreatefromstring($sanitized));
    }

    public function test_sanitizer_returns_null_for_non_image_bytes(): void
    {
        $sanitized = (new ImageSanitizer)->sanitize('%PDF-1.4 not really a pdf but definitely not an image');

        $this->assertNull($sanitized);
    }

    public function test_the_job_overwrites_stored_bytes_with_the_sanitized_version(): void
    {
        Storage::fake('public');
        $original = $this->makeJpegWithExif();
        Storage::disk('public')->put('properties/1/images/test.jpg', $original);

        (new SanitizeUploadedImageJob('public', 'properties/1/images/test.jpg'))->handle(new ImageSanitizer);

        $stored = Storage::disk('public')->get('properties/1/images/test.jpg');
        $this->assertNotSame($original, $stored);
    }

    public function test_the_job_leaves_non_image_files_untouched(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('kyc/1/doc.pdf', '%PDF-1.4 fake pdf content');

        (new SanitizeUploadedImageJob('local', 'kyc/1/doc.pdf'))->handle(new ImageSanitizer);

        $this->assertSame('%PDF-1.4 fake pdf content', Storage::disk('local')->get('kyc/1/doc.pdf'));
    }

    private function makeJpegWithExif(): string
    {
        $image = imagecreatetruecolor(10, 10);
        imagefill($image, 0, 0, imagecolorallocate($image, 10, 20, 30));
        ob_start();
        imagejpeg($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        // Simulate an image with trailing junk appended after the JPEG's
        // own EOI marker — a classic polyglot trick GD-based re-encoding
        // discards entirely, since GD only ever reads pixel data back out.
        return $bytes.'<script>alert(1)</script>';
    }
}
