<?php

namespace App\Services\Content;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * 05-security-compliance.md: "Rich text (property descriptions)
 * sanitized with HTMLPurifier before storage/display — XSS vector."
 */
class HtmlSanitizer
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,b,strong,i,em,ul,ol,li');
        $config->set('Cache.SerializerPath', storage_path('app/htmlpurifier-cache'));

        $this->purifier = new HTMLPurifier($config);
    }

    public function clean(?string $html): ?string
    {
        return $html === null ? null : $this->purifier->purify($html);
    }
}
