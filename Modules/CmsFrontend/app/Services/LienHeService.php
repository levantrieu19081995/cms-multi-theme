<?php

namespace Modules\CmsFrontend\Services;

use Modules\CmsFrontend\Models\LienHe;

class LienHeService
{
    public function store(array $data): LienHe
    {
        return LienHe::create($data);
    }
}
