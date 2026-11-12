<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use Modules\CmsBackend\DataProviders\SocialPublicationDataProvider;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Services\Social\SocialPublicationService;
use Modules\CmsBackend\Services\Social\SocialStatistics;

/**
 * "Tổng quan - thống kê": the overview of the multi-channel screens and the statistics in one page. A section is only prepared (and
 * shown) when the user holds its permission: the overview and the recent requests need the publication view permission, the
 * statistics need the statistics permission. Either one opens the page.
 */
class SocialOverviewController extends SocialBaseController
{
    public function __construct(
        SocialPublicationDataProvider $data,
        private SocialPublicationService $publications,
        private SocialStatistics $statistics,
    ) {
        parent::__construct($data);
        $this->middleware($this->requires(PermissionsEnum::SOCIAL_PUBLICATION_VIEW, PermissionsEnum::SOCIAL_STATISTICS_VIEW));
        $this->ensureInstalled();
    }

    public function index()
    {
        $can = $this->abilities();
        $showOverview = $can[PermissionsEnum::SOCIAL_PUBLICATION_VIEW->value];
        $showStatistics = $can[PermissionsEnum::SOCIAL_STATISTICS_VIEW->value];

        return view('cmsbackend::screens.pages.truyen-thong.overview', [
            'overview' => $showOverview ? $this->statistics->overview() : null,
            'recent' => $showOverview ? $this->data->paginatePublications([], 5) : null,
            'stats' => $showStatistics ? $this->statistics->statistics() : null,
            'can' => $can,
        ]);
    }
}
