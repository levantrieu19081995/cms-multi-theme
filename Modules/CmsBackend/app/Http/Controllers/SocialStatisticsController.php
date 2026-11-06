<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use Modules\CmsBackend\DataProviders\SocialPublicationDataProvider;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Services\Social\SocialPublicationService;
use Modules\CmsBackend\Services\Social\SocialStatistics;

class SocialStatisticsController extends SocialBaseController
{
    public function __construct(
        SocialPublicationDataProvider $data,
        private SocialPublicationService $publications,
        private SocialStatistics $statistics,
    ) {
        parent::__construct($data);
        $this->middleware($this->requires(PermissionsEnum::SOCIAL_STATISTICS_VIEW));
        $this->ensureInstalled();
    }

    public function index()
    {
        return view('cmsbackend::screens.pages.truyen-thong.statistics', [
            'stats' => $this->statistics->statistics(),
        ]);
    }
}
