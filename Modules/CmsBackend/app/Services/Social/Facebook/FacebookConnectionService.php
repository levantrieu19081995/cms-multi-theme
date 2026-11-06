<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social\Facebook;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Enums\Social\SocialAccountStatus;
use Modules\CmsBackend\Enums\Social\SocialChannel;
use Modules\CmsBackend\Models\Social\SocialAccount;
use Modules\CmsBackend\Services\Social\SocialException;
use Modules\CmsBackend\Services\Social\SocialOperationResult;
use Modules\CmsBackend\Services\Social\SocialPermissions;
use Modules\CmsBackend\Support\ProjectContext;

/**
 * Connect, check and disconnect the Facebook Page of the current project. Phase 2A: nothing is posted or deleted.
 *
 * The project is always ProjectContext::key(), never a request value. The page access token is never taken from a request:
 * it comes from the OAuth selection kept on the server (FacebookOAuthStore) and is stored in social_accounts.credentials
 * (encrypted cast). Activity logs hold ids and statuses only.
 */
final class FacebookConnectionService
{
    private const MODULE = 'Tài khoản mạng xã hội';

    public function __construct(
        private FacebookGraphClient $graph,
        private FacebookSettings $settings,
        private FacebookOAuthStore $store,
        private FacebookChannelAdapter $adapter,
    ) {}

    public function configured(): bool
    {
        return $this->settings->configured();
    }

    // ------------------------------------------------------------------ 1. start

    /**
     * Address of the Meta login dialog for the current project and user. The optional hints are only compared with the
     * pages Meta returns; they do not replace the login or the token.
     *
     * @param  array<string, mixed>  $input  expected_page_id, expected_page_url, business_id
     */
    public function start(Session $session, User $user, array $input): string
    {
        $this->authorize($user);
        $this->requireConfigured();

        $state = $this->store->begin($session, $user, ProjectContext::key(), self::cleanExpected($input));

        return $this->graph->authorizationUrl($state);
    }

    // ------------------------------------------------------------------ 2. callback

    /**
     * Turn the callback of Meta into a server-side selection of pages. Nothing is saved in the database here.
     *
     * @param  array<string, mixed>  $query
     *
     * @throws SocialException
     */
    public function handleCallback(Session $session, User $user, array $query): void
    {
        $this->authorize($user);
        $this->requireConfigured();

        if (! empty($query['error'])) {
            $this->store->discard($session);
            throw new SocialException(__('admin.social.fb_error_denied'), 'denied');
        }
        $code = (string) ($query['code'] ?? '');
        $state = (string) ($query['state'] ?? '');
        if ($code === '' || $state === '') {
            throw new SocialException(__('admin.social.fb_error_state_invalid'), 'state_invalid');
        }

        $project = ProjectContext::key();
        $attempt = $this->store->consume($session, $state, $user, $project);
        $expected = (array) ($attempt['expected'] ?? []);

        try {
            $userToken = $this->graph->extendUserToken($this->graph->exchangeCode($code));

            $info = $this->graph->inspectToken($userToken);
            if ((string) ($info['app_id'] ?? '') !== $this->settings->appId()) {
                throw new SocialException(__('admin.social.fb_error_app_mismatch'), 'app_mismatch');
            }
            if (empty($info['is_valid'])) {
                throw new SocialException(__('admin.social.fb_api_auth_expired'), 'auth_expired');
            }

            $granted = $this->graph->grantedPermissions($userToken);
            $missing = array_values(array_diff($this->settings->scopes(), $granted));
            if ($missing !== []) {
                throw new SocialException(__('admin.social.fb_error_scopes_missing', ['permissions' => implode(', ', $missing)]), 'scopes_missing');
            }

            $me = $this->graph->me($userToken);
            $pages = $this->graph->managedPages($userToken);
        } catch (FacebookApiException $e) {
            throw new SocialException($e->userMessage(), 'facebook_'.$e->kind);
        }

        if ($pages === []) {
            throw new SocialException(__('admin.social.fb_error_no_pages'), 'no_pages');
        }
        $pages = array_map(fn (array $page) => $page + $this->assess($page, $expected), $pages);
        if (($expected['page_id'] ?? null) !== null || ($expected['page_url'] ?? null) !== null) {
            if (! in_array(true, array_column($pages, 'allowed'), true)) {
                throw new SocialException(__('admin.social.fb_error_expected_not_found'), 'expected_not_found');
            }
        }

        $this->store->putSelection($session, [
            'user_id' => $user->id,
            'project_key' => $project,
            'expected' => $expected,
            'meta_user_id' => $me['id'],
            'granted' => $granted,
            'pages' => $pages,
        ]);
    }

    // ------------------------------------------------------------------ 3. choose a page

    /**
     * What the page-choice screen shows. The page access tokens are removed here: they never leave the server.
     *
     * @return array{pages: array<int, array<string, mixed>>, expected: array<string, mixed>, current: ?SocialAccount}
     *
     * @throws SocialException
     */
    public function selectionView(Session $session, User $user): array
    {
        $this->authorize($user);
        $selection = $this->store->selection($session, $user, ProjectContext::key());

        return [
            'pages' => array_map(fn (array $page) => array_diff_key($page, ['access_token' => true]), $selection['pages']),
            'expected' => (array) $selection['expected'],
            'current' => $this->activeAccounts(ProjectContext::key())->first(),
        ];
    }

    /**
     * Verify the chosen page with Facebook again and save the connection.
     *
     * @throws SocialException
     */
    public function confirm(Session $session, User $user, string $pageId, bool $replace): SocialAccount
    {
        $this->authorize($user);
        $this->requireConfigured();

        $project = ProjectContext::key();
        $selection = $this->store->selection($session, $user, $project);

        $page = null;
        foreach ($selection['pages'] as $candidate) {
            if ((string) $candidate['id'] === $pageId) {
                $page = $candidate;
                break;
            }
        }
        if ($page === null || empty($page['eligible']) || empty($page['allowed']) || ! is_string($page['access_token'] ?? null)) {
            throw new SocialException(__('admin.social.fb_error_page_not_selectable'), 'page_not_selectable');
        }

        try {
            $verified = $this->adapter->verifyPage($pageId, $page['access_token'], (array) $selection['granted']);
        } catch (FacebookApiException $e) {
            throw new SocialException($e->userMessage(), 'facebook_'.$e->kind);
        }
        $fresh = $verified['page'];

        [$account, $replaced, $created] = DB::transaction(function () use ($project, $pageId, $user, $replace, $fresh, $page, $verified, $selection) {
            $active = $this->activeAccounts($project, true);
            $others = $active->filter(fn (SocialAccount $a) => (string) $a->external_account_id !== $pageId);
            if ($others->isNotEmpty() && ! $replace) {
                $current = $others->first();
                throw new SocialException(__('admin.social.fb_error_replace_required', ['name' => $current->name, 'id' => $current->external_account_id]), 'replace_required');
            }
            foreach ($others as $old) {
                $old->update(['status' => SocialAccountStatus::DISCONNECTED->value, 'credentials' => null]);
            }

            $account = SocialAccount::query()->firstOrNew([
                'project_key' => $project,
                'platform' => SocialChannel::FACEBOOK->value,
                'external_account_id' => $pageId,
            ]);
            $created = ! $account->exists;
            $account->fill([
                'name' => $fresh['name'] !== '' ? $fresh['name'] : $page['name'],
                'status' => SocialAccountStatus::CONNECTED->value,
                'credentials' => [
                    'page_access_token' => $page['access_token'],
                    'page_url' => $fresh['link'],
                    'picture_url' => $fresh['picture_url'],
                    'granted_permissions' => $verified['scopes'],
                    'token_type' => 'page',
                    'meta_user_id' => $selection['meta_user_id'] ?? null,
                ],
                'token_expires_at' => $verified['expires_at'] !== null ? Carbon::createFromTimestamp($verified['expires_at']) : null,
                'last_checked_at' => now(),
                'connected_by' => $user->id,
            ])->save();

            return [$account, $others->pluck('external_account_id')->all(), $created];
        });

        $this->store->forgetSelection($session);
        $this->log($created ? 'create' : 'update', $account, $user, __($replaced !== [] ? 'admin.social.log_fb_replaced' : 'admin.social.log_fb_connected'), ['replaced_page_ids' => $replaced]);

        return $account;
    }

    // ------------------------------------------------------------------ 4. check

    /**
     * Ask Facebook whether the stored connection still works, and record the answer. A network or rate-limit failure
     * changes nothing (the state is unknown); a definite answer from Meta updates the status.
     *
     * @return array{0: SocialAccount, 1: SocialOperationResult}
     *
     * @throws SocialException
     */
    public function check(User $user): array
    {
        $this->authorize($user);
        $this->requireConfigured();

        $account = $this->activeAccounts(ProjectContext::key())->first();
        if ($account === null) {
            throw new SocialException(__('admin.social.fb_error_no_account'), 'no_account');
        }

        $result = $this->adapter->validateConnection($account);
        if ($result->success) {
            $credentials = (array) $account->credentials;
            $credentials['page_url'] = $result->remoteUrl ?? ($credentials['page_url'] ?? null);
            $credentials['picture_url'] = $result->data['picture_url'] ?? ($credentials['picture_url'] ?? null);
            $credentials['granted_permissions'] = $result->data['scopes'] ?? ($credentials['granted_permissions'] ?? []);
            $expires = $result->data['expires_at'] ?? null;
            $account->fill([
                'name' => ($result->data['name'] ?? '') !== '' ? $result->data['name'] : $account->name,
                'status' => SocialAccountStatus::CONNECTED->value,
                'credentials' => $credentials,
                'token_expires_at' => $expires !== null ? Carbon::createFromTimestamp((int) $expires) : null,
                'last_checked_at' => now(),
            ])->save();
        } else {
            $status = match ($result->errorCode) {
                FacebookApiException::AUTH_EXPIRED => SocialAccountStatus::EXPIRED,
                FacebookApiException::AUTH_REVOKED => SocialAccountStatus::REVOKED,
                FacebookApiException::NETWORK, FacebookApiException::RATE_LIMIT => null,
                default => SocialAccountStatus::ERROR,
            };
            if ($status !== null) {
                $account->update(['status' => $status->value, 'last_checked_at' => now()]);
            }
        }
        $this->log('update', $account, $user, __('admin.social.log_fb_checked'), ['result' => $result->success ? 'ok' : (string) $result->errorCode]);

        return [$account, $result];
    }

    // ------------------------------------------------------------------ 5. disconnect

    /**
     * Disconnect in the CMS only: the token and every credential are erased, the page name and id are kept. Meta's grant
     * is not revoked (the same Meta App may serve other projects).
     *
     * @throws SocialException
     */
    public function disconnect(User $user): SocialAccount
    {
        $this->authorize($user);

        $accounts = $this->activeAccounts(ProjectContext::key(), true);
        if ($accounts->isEmpty()) {
            throw new SocialException(__('admin.social.fb_error_no_account'), 'no_account');
        }
        $account = DB::transaction(function () use ($accounts) {
            foreach ($accounts as $account) {
                $account->update(['status' => SocialAccountStatus::DISCONNECTED->value, 'credentials' => null, 'token_expires_at' => null]);
            }

            return $accounts->first();
        });
        $this->log('update', $account, $user, __('admin.social.log_fb_disconnected'));

        return $account;
    }

    // ------------------------------------------------------------------ view data

    /**
     * Everything the Facebook card shows. Never the token.
     *
     * @return array<string, mixed>
     */
    public function card(): array
    {
        $account = $this->activeAccounts(ProjectContext::key())->first();
        $credentials = $account !== null ? (array) $account->credentials : [];

        return [
            'configured' => $this->configured(),
            'config_problem' => ($key = $this->settings->problem()) !== null ? __('admin.social.'.$key) : null,
            'account' => $account,
            'connected' => $account !== null && $account->isConnected(),
            'page_url' => $credentials['page_url'] ?? null,
            'picture_url' => $credentials['picture_url'] ?? null,
            'connected_by' => $account?->connected_by ? User::query()->whereKey($account->connected_by)->value('name') : null,
        ];
    }

    // ------------------------------------------------------------------ helpers

    /** Facebook accounts of the current project that are not disconnected (normally one). */
    private function activeAccounts(string $project, bool $lock = false): Collection
    {
        $query = SocialAccount::query()
            ->where('project_key', $project)
            ->where('platform', SocialChannel::FACEBOOK->value)
            ->where('status', '!=', SocialAccountStatus::DISCONNECTED->value)
            ->latest('id');

        return ($lock ? $query->lockForUpdate() : $query)->get();
    }

    /**
     * May the person pick this page? It needs a page token, a role that can post, and must match the hints when given.
     *
     * @return array{eligible: bool, allowed: bool, reason: ?string}
     */
    private function assess(array $page, array $expected): array
    {
        $reason = null;
        if (! is_string($page['access_token'] ?? null) || $page['access_token'] === '') {
            $reason = 'no_token';
        } elseif (array_intersect(['CREATE_CONTENT', 'MANAGE'], $page['tasks']) === []) {
            $reason = 'no_role';
        }

        $allowed = true;
        if (($expected['page_id'] ?? null) !== null && (string) $page['id'] !== (string) $expected['page_id']) {
            $allowed = false;
        }
        if ($allowed && ($expected['page_url'] ?? null) !== null && ! self::sameFacebookUrl((string) $expected['page_url'], (string) $page['link'], (string) $page['id'])) {
            $allowed = false;
        }

        return ['eligible' => $reason === null, 'allowed' => $allowed, 'reason' => $reason];
    }

    /**
     * Hints typed by the administrator, cleaned. Anything that does not look right becomes null.
     *
     * @param  array<string, mixed>  $input
     * @return array{page_id: ?string, page_url: ?string, business_id: ?string}
     */
    public static function cleanExpected(array $input): array
    {
        $digits = fn ($v) => is_string($v) && preg_match('/^\d{5,25}$/', trim($v)) === 1 ? trim($v) : null;
        $url = null;
        $raw = trim((string) ($input['expected_page_url'] ?? ''));
        if ($raw !== '' && filter_var($raw, FILTER_VALIDATE_URL) !== false) {
            $parts = parse_url($raw);
            $host = strtolower((string) ($parts['host'] ?? ''));
            if (($parts['scheme'] ?? '') === 'https' && in_array($host, ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'web.facebook.com', 'fb.com', 'www.fb.com'], true)) {
                $url = $raw;
            }
        }

        return [
            'page_id' => $digits($input['expected_page_id'] ?? null),
            'page_url' => $url,
            'business_id' => $digits($input['business_id'] ?? null),
        ];
    }

    /** Same page address, ignoring scheme, "www.", a trailing slash and letter case; a profile.php?id= address is matched by id. */
    public static function sameFacebookUrl(string $expected, string $actual, string $pageId): bool
    {
        $normalize = function (string $url): string {
            $parts = parse_url(strtolower(trim($url)));

            return trim(($parts['path'] ?? ''), '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
        };
        $wanted = $normalize($expected);
        if (preg_match('/^profile\.php\?id=(\d+)/', $wanted, $m) === 1) {
            return $m[1] === $pageId;
        }

        return $wanted !== '' && ($wanted === $normalize($actual) || $wanted === $pageId);
    }

    private function authorize(User $user): void
    {
        if (! SocialPermissions::can($user, PermissionsEnum::SOCIAL_ACCOUNT_MANAGE)) {
            throw SocialException::forbidden();
        }
    }

    private function requireConfigured(): void
    {
        if (! $this->settings->configured()) {
            throw new SocialException(__('admin.social.fb_not_configured'), 'not_configured');
        }
    }

    /** @param array<string, mixed> $extra */
    private function log(string $action, SocialAccount $account, User $user, string $message, array $extra = []): void
    {
        // Project key, page id, user id, status and time only. No token, no credentials.
        ActivityLogger::log($action, self::MODULE, $account, [
            'project_key' => $account->project_key,
            'page_id' => $account->external_account_id,
            'user_id' => $user->id,
            'status' => $account->status->value,
        ] + $extra, $message);
    }
}
