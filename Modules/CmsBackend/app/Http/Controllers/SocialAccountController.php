<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use Modules\CmsBackend\DataProviders\SocialPublicationDataProvider;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Services\Social\Facebook\FacebookConnectionService;
use Modules\CmsBackend\Services\Social\Zalo\ZaloConnectionService;

/**
 * Linked accounts of the current project: one page with a column per platform, Facebook (SocialFacebookController) and Zalo OA
 * (SocialZaloController). Nothing here shows a token or a secret.
 */
class SocialAccountController extends SocialBaseController
{
    public function __construct(
        SocialPublicationDataProvider $data,
        private FacebookConnectionService $facebook,
        private ZaloConnectionService $zalo,
    ) {
        parent::__construct($data);
        $this->middleware($this->requires(PermissionsEnum::SOCIAL_ACCOUNT_MANAGE));
        $this->ensureInstalled();
    }

    public function index()
    {
        return view('cmsbackend::screens.pages.truyen-thong.accounts', [
            'facebook' => $this->facebook->card(),
            'zalo' => $this->zalo->card(),
        ]);
    }
}
