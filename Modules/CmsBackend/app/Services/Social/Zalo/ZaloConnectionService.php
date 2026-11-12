<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social\Zalo;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Contracts\Session\Session;
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
 * Connect, check and disconnect the Zalo Official Account of the current project.
 *
 * Unlike Facebook there is no list to choose from: the person grants access to one OA, Zalo answers with a code and the OA id,
 * and the connection is saved at once. The project is always ProjectContext::key(), never a request value. Tokens come from the
 * code exchange on the server and are stored in social_accounts.credentials (encrypted cast); activity logs hold ids and statuses only.
 */
final class ZaloConnectionService
{
    private const MODULE = 'Tài khoản mạng xã hội';

    public function __construct(
        private ZaloApiClient $client,
        private ZaloSettings $settings,
        private ZaloOAuthStore $store,
        private ZaloChannelAdapter $adapter,
    ) {}

    public function configured(): bool
    {
        return $this->settings->configured();
    }

    // ------------------------------------------------------------------ 1. start

    /**
     * Address of the Zalo permission page for the current project and user.
     *
     * @throws SocialException
     */
    public function start(Session $session, User $user): string
    {
        $this->authorize($user);
        $this->requireConfigured();

        $current = $this->activeAccounts(ProjectContext::key())->first(fn (SocialAccount $account) => $account->isConnected());
        if ($current !== null) {
            throw new SocialException(__('admin.social.zalo_error_already_connected', ['name' => $current->name]), 'already_connected');
        }

        $attempt = $this->store->begin($session, $user, ProjectContext::key());

        return $this->client->authorizationUrl($attempt['state'], $attempt['challenge']);
    }

    // ------------------------------------------------------------------ 2. callback

    /**
     * Turn the callback of Zalo into a saved connection.
     *
     * @param  array<string, mixed>  $query  code, oa_id, state, error
     *
     * @throws SocialException
     */
    public function handleCallback(Session $session, User $user, array $query): SocialAccount
    {
        $this->authorize($user);
        $this->requireConfigured();

        if (! empty($query['error'])) {
            $this->store->discard($session);
            throw new SocialException(__('admin.social.zalo_error_denied'), 'denied');
        }
        $code = (string) ($query['code'] ?? '');
        $state = (string) ($query['state'] ?? '');
        if ($code === '' || $state === '') {
            throw new SocialException(__('admin.social.zalo_error_state_invalid'), 'state_invalid');
        }

        $project = ProjectContext::key();
        $attempt = $this->store->consume($session, $state, $user, $project);

        try {
            $tokens = $this->client->exchangeCode($code, (string) $attempt['verifier']);
            $oa = $this->client->oaInfo($tokens['access_token']);
        } catch (ZaloApiException $e) {
            throw new SocialException($e->userMessage(), 'zalo_'.$e->kind);
        }

        // The OA Zalo names in the callback must be the one the token reads.
        $named = trim((string) ($query['oa_id'] ?? ''));
        if ($named !== '' && $named !== $oa['oa_id']) {
            throw new SocialException(__('admin.social.zalo_error_oa_mismatch'), 'oa_mismatch');
        }

        $account = DB::transaction(function () use ($project, $user, $tokens, $oa) {
            // Another OA left over from an earlier attempt (expired or in error) is replaced.
            foreach ($this->activeAccounts($project, true) as $old) {
                if ((string) $old->external_account_id !== $oa['oa_id']) {
                    $old->update(['status' => SocialAccountStatus::DISCONNECTED->value, 'credentials' => null, 'token_expires_at' => null]);
                }
            }

            $account = SocialAccount::query()->firstOrNew([
                'project_key' => $project,
                'platform' => SocialChannel::ZALO->value,
                'external_account_id' => $oa['oa_id'],
            ]);
            $account->fill([
                'name' => $oa['name'] !== '' ? $oa['name'] : $oa['oa_id'],
                'status' => SocialAccountStatus::CONNECTED->value,
                'credentials' => [
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'access_expires_at' => time() + $tokens['expires_in'],
                ] + $this->profile($oa),
                // The life of the connection is the life of the refresh token.
                'token_expires_at' => now()->addMonths($this->settings->refreshTokenMonths()),
                'last_checked_at' => now(),
                'connected_by' => $user->id,
            ])->save();

            return $account;
        });

        $this->log('create', $account, $user, __('admin.social.log_zalo_connected'));

        return $account;
    }

    // ------------------------------------------------------------------ 3. check

    /**
     * Ask Zalo whether the stored connection still works, and record the answer. A network or rate-limit failure changes nothing
     * (the state is unknown); a definite answer from Zalo updates the status.
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
            throw new SocialException(__('admin.social.zalo_error_no_account'), 'no_account');
        }

        $result = $this->adapter->validateConnection($account);
        if ($result->success) {
            $credentials = (array) $account->credentials;
            $account->fill([
                'name' => ($result->data['name'] ?? '') !== '' ? $result->data['name'] : $account->name,
                'status' => SocialAccountStatus::CONNECTED->value,
                'credentials' => $this->profile($result->data) + $credentials,
                'last_checked_at' => now(),
            ])->save();
        } else {
            $status = match ($result->errorCode) {
                ZaloApiException::AUTH_EXPIRED => SocialAccountStatus::EXPIRED,
                ZaloApiException::NETWORK, ZaloApiException::RATE_LIMIT => null,
                default => SocialAccountStatus::ERROR,
            };
            if ($status !== null) {
                $account->update(['status' => $status->value, 'last_checked_at' => now()]);
            }
        }
        $this->log('update', $account, $user, __('admin.social.log_zalo_checked'), ['result' => $result->success ? 'ok' : (string) $result->errorCode]);

        return [$account, $result];
    }

    // ------------------------------------------------------------------ 4. disconnect

    /**
     * Disconnect in the CMS only: the tokens and every credential are erased, the OA name and id are kept. The grant at Zalo is
     * not revoked.
     *
     * @throws SocialException
     */
    public function disconnect(User $user): SocialAccount
    {
        $this->authorize($user);

        $accounts = $this->activeAccounts(ProjectContext::key(), true);
        if ($accounts->isEmpty()) {
            throw new SocialException(__('admin.social.zalo_error_no_account'), 'no_account');
        }
        $account = DB::transaction(function () use ($accounts) {
            foreach ($accounts as $account) {
                $account->update(['status' => SocialAccountStatus::DISCONNECTED->value, 'credentials' => null, 'token_expires_at' => null]);
            }

            return $accounts->first();
        });
        $this->log('update', $account, $user, __('admin.social.log_zalo_disconnected'));

        return $account;
    }

    // ------------------------------------------------------------------ view data

    /**
     * Everything the Zalo card shows. Never a token.
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
            'avatar' => $credentials['avatar'] ?? null,
            'alias' => $credentials['alias'] ?? null,
            'is_verified' => (bool) ($credentials['is_verified'] ?? false),
            'followers' => $credentials['followers'] ?? null,
            'package' => $credentials['package'] ?? null,
            'connected_by' => $account?->connected_by ? User::query()->whereKey($account->connected_by)->value('name') : null,
        ];
    }

    // ------------------------------------------------------------------ helpers

    /**
     * What is kept about the OA besides the tokens (shown on the card). Accepts the client's answer or the adapter's data.
     *
     * @param  array<string, mixed>  $oa
     * @return array<string, mixed>
     */
    private function profile(array $oa): array
    {
        return [
            'oa_id' => $oa['oa_id'] ?? null,
            'avatar' => $oa['avatar'] ?? null,
            'cover' => $oa['cover'] ?? null,
            'description' => $oa['description'] ?? null,
            'alias' => $oa['alias'] ?? null,
            'is_verified' => (bool) ($oa['is_verified'] ?? false),
            'followers' => $oa['followers'] ?? null,
            'package' => $oa['package'] ?? null,
        ];
    }

    /** Zalo accounts of the current project that are not disconnected (normally one). */
    private function activeAccounts(string $project, bool $lock = false): Collection
    {
        $query = SocialAccount::query()
            ->where('project_key', $project)
            ->where('platform', SocialChannel::ZALO->value)
            ->where('status', '!=', SocialAccountStatus::DISCONNECTED->value)
            ->latest('id');

        return ($lock ? $query->lockForUpdate() : $query)->get();
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
            throw new SocialException(__('admin.social.zalo_not_configured'), 'not_configured');
        }
    }

    /** @param array<string, mixed> $extra */
    private function log(string $action, SocialAccount $account, User $user, string $message, array $extra = []): void
    {
        // Project key, OA id, user id, status and time only. No token, no credentials.
        ActivityLogger::log($action, self::MODULE, $account, [
            'project_key' => $account->project_key,
            'oa_id' => $account->external_account_id,
            'user_id' => $user->id,
            'status' => $account->status->value,
        ] + $extra, $message);
    }
}
