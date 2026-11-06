<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\FileViewFinder;
use Modules\CmsBackend\Services\ScreenTheme;

final class ScreenViewFinder extends FileViewFinder
{
    public function __construct(Filesystem $files, FileViewFinder $original, private ScreenTheme $screens)
    {
        parent::__construct($files, $original->getPaths(), $original->getExtensions());
        $this->hints = $original->getHints();
    }

    public function find(mixed $name): string
    {
        return $this->screens->pathForView((string) $name) ?? parent::find($name);
    }
}
