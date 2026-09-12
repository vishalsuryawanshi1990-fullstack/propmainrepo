<?php

namespace App\Services\Content;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Facades\File;

/**
 * 05-security-compliance.md: "Rich text (property descriptions)
 * sanitized with HTMLPurifier before storage/display — XSS vector."
 */
class HtmlSanitizer
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        // Created at runtime rather than relying solely on a committed
        // empty directory — a placeholder .gitignore in an empty
        // directory is easy to miss staging (as happened here once
        // already) and a fresh clone/Docker build shouldn't depend on
        // git having preserved it.
        $cachePath = storage_path('app/htmlpurifier-cache');
        File::ensureDirectoryExists($cachePath);

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,b,strong,i,em,ul,ol,li');
        $config->set('Cache.SerializerPath', $cachePath);

        $this->purifier = new HTMLPurifier($config);
    }

    public function clean(?string $html): ?string
    {
        return $html === null ? null : $this->purifier->purify($html);
    }
}
