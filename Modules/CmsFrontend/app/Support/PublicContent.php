<?php

namespace Modules\CmsFrontend\Support;

final class PublicContent
{
    private const STORAGE_PATH = '/storage/cms-userfiles/';

    /**
     * Render old absolute links and new relative links on the active domain.
     */
    public static function forDisplay(?string $html): string
    {
        $html = (string) $html;

        return preg_replace(
            "~(?:https?:)?//[^/\"'\\s<>]+/storage/cms-userfiles/~i",
            self::STORAGE_PATH,
            $html
        ) ?? $html;
    }
}
