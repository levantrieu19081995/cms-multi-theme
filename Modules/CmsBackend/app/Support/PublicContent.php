<?php

namespace Modules\CmsBackend\Support;

final class PublicContent
{
    private const STORAGE_PATH = '/storage/cms-userfiles/';

    /**
     * Keep editor content independent from the domain that hosts CMS.
     */
    public static function forEditor(?string $html): string
    {
        return self::forStorage($html);
    }

    /**
     * Store CMS user-file links as root-relative paths so copied databases
     * continue to work on local, staging and customer domains.
     */
    public static function forStorage(?string $html): string
    {
        $html = (string) $html;

        return preg_replace(
            "~(?:https?:)?//[^/\"'\\s<>]+/storage/cms-userfiles/~i",
            self::STORAGE_PATH,
            $html
        ) ?? $html;
    }
}
