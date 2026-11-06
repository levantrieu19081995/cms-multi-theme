<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use Modules\CmsBackend\DataProviders\SocialPublicationDataProvider;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Services\Social\Facebook\FacebookConnectionService;

/**
 * Linked accounts of the current project. Facebook can be connected (Phase 2A, see SocialFacebookController); Zalo is
 * not available yet. Nothing here shows a token or a secret.
 */
class SocialAccountController extends SocialBaseController
{
    public function __construct(SocialPublicationDataProvider $data, private FacebookConnectionService $facebook)
    {
        parent::__construct($data);
        $this->middleware($this->requires(PermissionsEnum::SOCIAL_ACCOUNT_MANAGE));
        $this->ensureInstalled();
    }

    public function index()
    {
        return view('cmsbackend::screens.pages.truyen-thong.accounts', ['facebook' => $this->facebook->card()]);
    }
}
