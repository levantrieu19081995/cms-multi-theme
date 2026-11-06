<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use App\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\CmsBackend\DataProviders\SocialPublicationDataProvider;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Services\Social\Facebook\FacebookConnectionService;
use Modules\CmsBackend\Services\Social\SocialException;
use Modules\CmsBackend\Support\ModuleUrl;

/**
 * Connect the Facebook Page of the current project. Every route is behind cms.auth, the "social-account-manage" permission
 * and (POST) CSRF. The project comes from ProjectContext inside the service, never from the request; no token or secret is
 * ever read from or written to a request, a URL or a view.
 */
class SocialFacebookController extends SocialBaseController
{
    private const ROUTE = 'cms.dashboard.truyen-thong.';

    public function __construct(SocialPublicationDataProvider $data, private FacebookConnectionService $facebook)
    {
        parent::__construct($data);
        $this->middleware($this->requires(PermissionsEnum::SOCIAL_ACCOUNT_MANAGE));
        $this->ensureInstalled();
    }

    /** Start the OAuth login at Meta. */
    public function connect(Request $request): RedirectResponse
    {
        $input = Validator::make($request->query(), [
            'expected_page_id' => ['nullable', 'regex:/^\d{5,25}$/'],
            'expected_page_url' => ['nullable', 'url:https', 'max:500'],
            'business_id' => ['nullable', 'regex:/^\d{5,25}$/'],
        ], [], [
            'expected_page_id' => __('admin.social.fb_field_page_id'),
            'expected_page_url' => __('admin.social.fb_field_page_url'),
            'business_id' => __('admin.social.fb_field_business_id'),
        ]);
        if ($input->fails()) {
            return $this->fail($input->errors()->first());
        }

        try {
            $url = $this->facebook->start($request->session(), $request->user(), $input->validated());
        } catch (SocialException $e) {
            return $this->fail($e->getMessage());
        }

        return redirect()->away($url);
    }

    /** Return address registered at Meta. It never renders a page: it ends in a redirect so the code leaves the address bar. */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $this->facebook->handleCallback($request->session(), $request->user(), $request->only(['code', 'state', 'error']));
        } catch (SocialException $e) {
            return $this->noReferrer($this->fail($e->getMessage()));
        }

        return $this->noReferrer(redirect(ModuleUrl::route(self::ROUTE.'facebook.select-page')));
    }

    public function selectPage(Request $request)
    {
        try {
            $view = $this->facebook->selectionView($request->session(), $request->user());
        } catch (SocialException $e) {
            return $this->fail($e->getMessage());
        }

        return view('cmsbackend::screens.pages.truyen-thong.facebook-select', $view);
    }

    public function confirmPage(Request $request): RedirectResponse
    {
        $data = Validator::make($request->all(), [
            'page_id' => ['required', 'regex:/^\d{5,25}$/'],
            'replace' => ['nullable', 'boolean'],
        ]);
        if ($data->fails()) {
            return redirect(ModuleUrl::route(self::ROUTE.'facebook.select-page'));
        }

        try {
            $account = $this->facebook->confirm($request->session(), $request->user(), (string) $data->validated()['page_id'], (bool) ($data->validated()['replace'] ?? false));
        } catch (SocialException $e) {
            $this->flashError($e->getMessage());

            // The page list is still held on the server: go back to choose again (for example to tick "replace").
            return in_array($e->errorCode, ['replace_required', 'page_not_selectable'], true)
                ? redirect(ModuleUrl::route(self::ROUTE.'facebook.select-page'))
                : redirect(ModuleUrl::route(self::ROUTE.'accounts'));
        }

        Toastr::success(__('admin.social.fb_connected', ['name' => $account->name]), __('admin.message.thanh_cong'));

        return redirect(ModuleUrl::route(self::ROUTE.'accounts'));
    }

    public function check(Request $request): RedirectResponse
    {
        try {
            [$account, $result] = $this->facebook->check($request->user());
        } catch (SocialException $e) {
            return $this->fail($e->getMessage());
        }

        $result->success
            ? Toastr::success(__('admin.social.fb_check_ok'), __('admin.message.thanh_cong'))
            : $this->flashError((string) $result->message);

        return redirect(ModuleUrl::route(self::ROUTE.'accounts'));
    }

    public function disconnect(Request $request): RedirectResponse
    {
        try {
            $this->facebook->disconnect($request->user());
        } catch (SocialException $e) {
            return $this->fail($e->getMessage());
        }

        Toastr::success(__('admin.social.fb_disconnected'), __('admin.message.thanh_cong'));

        return redirect(ModuleUrl::route(self::ROUTE.'accounts'));
    }

    private function fail(string $message): RedirectResponse
    {
        $this->flashError($message);

        return redirect(ModuleUrl::route(self::ROUTE.'accounts'));
    }

    private function noReferrer(RedirectResponse $response): RedirectResponse
    {
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
