<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Services;

/** Default homepage blocks use the fixed grid presentation; public themes override their screens. */
class HomeTheme
{
    private const NAMESPACE = 'cmsfrontend::screens.components.home.variants.';

    public function key(): string
    {
        return 'bao-chi';
    }

    public function label(): string
    {
        return 'Mặc định';
    }

    public function view(string $block): string
    {
        return self::NAMESPACE.$this->key().'.'.$block;
    }
}
