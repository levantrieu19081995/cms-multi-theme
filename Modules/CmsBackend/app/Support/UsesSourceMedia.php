<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Support;

trait UsesSourceMedia
{
    public function getMorphClass(): string
    {
        return parent::getMorphClass();
    }
}
