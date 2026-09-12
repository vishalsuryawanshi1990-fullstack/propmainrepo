<?php

namespace App\Services\Media;

/**
 * A small GD-based difference hash (dHash), used for the "same flat
 * re-posted by multiple owners" duplicate-listing check in
 * 05-security-compliance.md. No external dependency beyond GD, which ships
 * with PHP — the hash is stored as a 64-character "0"/"1" string rather
 * than hex, so no bignum extension is needed to compare hashes either.
 */
class PerceptualHash
{
    public function hashContents(?string $contents): ?string
    {
        $image = $contents !== null ? @imagecreatefromstring($contents) : false;

        if ($image === false) {
            return null;
        }

        // 9x8 grayscale so each row's 8 adjacent pixels can diff into 64 bits.
        $resized = imagecreatetruecolor(9, 8);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, 9, 8, imagesx($image), imagesy($image));
        imagefilter($resized, IMG_FILTER_GRAYSCALE);

        $bits = '';

        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $left = imagecolorat($resized, $x, $y) & 0xFF;
                $right = imagecolorat($resized, $x + 1, $y) & 0xFF;
                $bits .= $left > $right ? '1' : '0';
            }
        }

        imagedestroy($image);
        imagedestroy($resized);

        return $bits;
    }

    /**
     * Hamming distance between two 64-bit hashes — 0 is identical, higher
     * means more different. A distance under ~10 is a reasonable
     * "probably the same photo" threshold.
     */
    public function distance(string $hashA, string $hashB): int
    {
        // XOR-ing the two "0"/"1" strings byte-wise: matching characters
        // cancel to \x00, differing ones become \x01 — so counting \x01
        // bytes counts differing bits without a manual loop or bignum lib.
        return substr_count($hashA ^ $hashB, "\x01");
    }
}
