<?php

namespace App\Services\Media;

/**
 * 05-security-compliance.md: "re-encode images server-side (strips
 * embedded scripts/EXIF)". Re-encoding through GD only ever reads pixel
 * data back out, so anything appended/embedded in the original file
 * (EXIF payloads, a polyglot GIF/HTML file, etc.) never survives.
 *
 * Always re-encodes to JPEG — property/KYC photos have no need for
 * transparency, and a single consistent output format means the
 * caller never has to reconcile a changed extension against the
 * original upload's.
 */
class ImageSanitizer
{
    /**
     * @return string|null the re-encoded bytes, or null if this isn't a
     *                     GD-decodable image (e.g. a PDF — those aren't
     *                     handled here at all)
     */
    public function sanitize(string $contents): ?string
    {
        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            return null;
        }

        $flattened = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($flattened, 0, 0, imagecolorallocate($flattened, 255, 255, 255));
        imagecopy($flattened, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        ob_start();
        imagejpeg($flattened, null, 90);
        $output = ob_get_clean();

        imagedestroy($image);
        imagedestroy($flattened);

        return $output === false ? null : $output;
    }
}
