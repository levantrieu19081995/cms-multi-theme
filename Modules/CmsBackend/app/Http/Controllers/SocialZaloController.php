<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use App\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\CmsBackend\DataProviders\SocialPublicationDataProvider;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Services\Social\SocialException;
use Modules\CmsBackend\Services\Social\Zalo\ZaloConnectionService;
use Modules\CmsBackend\Support\ModuleUrl;

/**
 * Connect the Zalo Official Account of the current project. Every route is behind cms.auth, the "social-account-manage"
 * permission and (POST) CSRF. The project comes from ProjectContext inside the service, never from the request; no token or secret
 * is ever read from or written to a request, a URL or a view.
 */
class SocialZaloController extends SocialBaseController
{
    private const ROUTE = 'cms.dashboard.truyen-thong.';

    public function __construct(SocialPublicationDataProvider $data, private ZaloConnectionService $zalo)
    {
        parent::__construct($data);
        $this->middleware($this->requires(PermissionsEnum::SOCIAL_ACCOUNT_MANAGE));
        $this->ensureInstalled();
    }

    /** Start the OAuth login at Zalo. */
    public function connect(Request $request): RedirectResponse
    {
        try {
            $url = $this->zalo->start($request->session(), $request->user());
        } catch (SocialException $e) {
            return $this->back($e->getMessage());
        }

        return redirect()->away($url);
    }

    /** Return address registered at Zalo. It never renders a page: it ends in a redirect so the code leaves the address bar. */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $account = $this->zalo->handleCallback($request->session(), $request->user(), $request->only(['code', 'oa_id', 'state', 'error']));
        } catch (SocialException $e) {
            return $this->noReferrer($this->back($e->getMessage()));
        }

        Toastr::success(__('admin.social.zalo_connected', ['name' => $account->name]), __('admin.message.thanh_cong'));

        return $this->noReferrer($this->back());
    }

    public function check(Request $request): RedirectResponse
    {
        try {
            [, $result] = $this->zalo->check($request->user());
        } catch (SocialException $e) {
            return $this->back($e->getMessage());
        }

        $result->success
            ? Toastr::success(__('admin.social.zalo_check_ok'), __('admin.message.thanh_cong'))
            : $this->flashError((string) $result->message);

        return $this->back();
    }

    public function disconnect(Request $request): RedirectResponse
    {
        try {
            $this->zalo->disconnect($request->user());
        } catch (SocialException $e) {
            return $this->back($e->getMessage());
        }

        Toastr::success(__('admin.social.zalo_disconnected'), __('admin.message.thanh_cong'));

        return $this->back();
    }

    /** Back to the Zalo tab of the accounts page, with an error message when given. */
    private function back(?string $error = null): RedirectResponse
    {
        if ($error !== null) {
            $this->flashError($error);
        }

        return redirect(ModuleUrl::route(self::ROUTE.'accounts').'#social-column-zalo');
    }

    private function noReferrer(RedirectResponse $response): RedirectResponse
    {
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
