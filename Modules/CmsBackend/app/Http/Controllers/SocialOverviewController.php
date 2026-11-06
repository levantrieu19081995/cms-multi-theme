<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use Modules\CmsBackend\DataProviders\SocialPublicationDataProvider;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Services\Social\SocialPublicationService;
use Modules\CmsBackend\Services\Social\SocialStatistics;

class SocialOverviewController extends SocialBaseController
{
    public function __construct(
        SocialPublicationDataProvider $data,
        private SocialPublicationService $publications,
        private SocialStatistics $statistics,
    ) {
        parent::__construct($data);
        $this->middleware($this->requires(PermissionsEnum::SOCIAL_PUBLICATION_VIEW));
        $this->ensureInstalled();
    }

    public function index()
    {
        return view('cmsbackend::screens.pages.truyen-thong.overview', [
            'overview' => $this->statistics->overview(),
            'recent' => $this->data->paginatePublications([], 5),
            'can' => $this->abilities(),
        ]);
    }
}
