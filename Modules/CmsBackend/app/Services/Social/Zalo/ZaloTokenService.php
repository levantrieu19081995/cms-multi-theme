<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social\Zalo;

use Illuminate\Support\Facades\DB;
use Modules\CmsBackend\Enums\Social\SocialAccountStatus;
use Modules\CmsBackend\Models\Social\SocialAccount;

/**
 * Keeps the access token of a Zalo account usable. The access token lives about 25 hours, the refresh token 3 months, and a refresh
 * token works ONCE: renewing returns the next refresh token, and losing it would force the OA to be connected again. So renewing
 * runs inside a transaction that locks the account row, re-reads the credentials (another process may have renewed meanwhile)
 * and stores both new tokens before the lock is released.
 *
 * `social_accounts.token_expires_at` is the expiry of the REFRESH token (the real life of the connection); the access token's own
 * expiry is `credentials.access_expires_at`. Tokens never appear in a result, a message or a log.
 */
final class ZaloTokenService
{
    public function __construct(private ZaloApiClient $client, private ZaloSettings $settings) {}

    /**
     * A valid access token of the account.
     *
     * @param  string|null  $rejected  an access token Zalo just refused (-216 / -220): renew even if it looks valid
     *
     * @throws ZaloApiException auth_expired when the refresh token is missing, used, expired or revoked: connect again
     */
    public function accessToken(SocialAccount $account, ?string $rejected = null): string
    {
        $credentials = (array) $account->credentials;
        if ($rejected === null && $this->usable($credentials)) {
            return (string) $credentials['access_token'];
        }

        return $this->renew($account, $rejected);
    }

    /** @param array<string, mixed> $credentials */
    private function usable(array $credentials): bool
    {
        $token = $credentials['access_token'] ?? null;

        return is_string($token) && $token !== ''
            && (int) ($credentials['access_expires_at'] ?? 0) > time() + $this->settings->accessTokenMargin();
    }

    private function renew(SocialAccount $account, ?string $rejected): string
    {
        try {
            return DB::transaction(function () use ($account, $rejected) {
                $locked = $account->exists ? SocialAccount::query()->lockForUpdate()->find($account->id) : null;
                $source = $locked ?? $account;
                $credentials = (array) $source->credentials;

                // Another process renewed while this one waited for the lock: use its token, never spend the refresh token twice.
                $current = $credentials['access_token'] ?? null;
                if (is_string($current) && $current !== '' && $current !== $rejected && $this->usable($credentials)) {
                    $this->follow($account, $source);

                    return $current;
                }

                $refresh = $credentials['refresh_token'] ?? null;
                if (! is_string($refresh) || $refresh === '') {
                    throw new ZaloApiException(ZaloApiException::AUTH_EXPIRED);
                }

                $tokens = $this->client->refresh($refresh);
                $credentials['access_token'] = $tokens['access_token'];
                $credentials['refresh_token'] = $tokens['refresh_token'];
                $credentials['access_expires_at'] = time() + $tokens['expires_in'];
                $source->fill([
                    'credentials' => $credentials,
                    'status' => SocialAccountStatus::CONNECTED->value,
                    'token_expires_at' => now()->addMonths($this->settings->refreshTokenMonths()),
                ]);
                if ($source->exists) {
                    $source->save();
                }
                $this->follow($account, $source);

                return $tokens['access_token'];
            });
        } catch (ZaloApiException $e) {
            // The refresh token was refused: the connection is over until someone connects again. (Outside the transaction, so it stays.)
            if ($e->kind === ZaloApiException::AUTH_EXPIRED && $account->exists) {
                $account->update(['status' => SocialAccountStatus::EXPIRED->value]);
            }

            throw $e;
        }
    }

    /** Make the model the caller holds show what was stored. */
    private function follow(SocialAccount $account, SocialAccount $source): void
    {
        if ($account !== $source) {
            $account->setRawAttributes($source->getAttributes(), true);
        }
    }
}
