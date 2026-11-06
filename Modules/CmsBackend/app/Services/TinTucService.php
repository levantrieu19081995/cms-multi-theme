<?php

namespace Modules\CmsBackend\Services;

use App\DataProviders\PostDataProvider;
use Modules\CmsBackend\DataProviders\TinTucDataProvider;

class TinTucService extends BaseService
{
    protected TinTucDataProvider $tinTucDataProvider;

    protected PostDataProvider $postDataProvider;

    public function __construct(PostDataProvider $postDataProvider, TinTucDataProvider $tinTucDataProvider)
    {
        parent::__construct($postDataProvider, $tinTucDataProvider);
        $this->tinTucDataProvider = $tinTucDataProvider;
        $this->postDataProvider = $postDataProvider;
    }

    public function getChiTietTinTucBySlug(string $slug)
    {
        return $this->tinTucDataProvider->getChiTietTinTucBySlug($slug);
    }
}
