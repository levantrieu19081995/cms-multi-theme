<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social\Facebook;

use Modules\CmsBackend\Enums\Social\SocialChannel;
use Modules\CmsBackend\Models\Social\SocialAccount;
use Modules\CmsBackend\Models\Social\SocialPublicationChannel;
use Modules\CmsBackend\Services\Social\SocialChannelAdapter;
use Modules\CmsBackend\Services\Social\SocialException;
use Modules\CmsBackend\Services\Social\SocialOperationResult;

/**
 * Facebook Page adapter. Phase 2A implements validateConnection() only. publish, remove, syncStatus and syncMetrics answer
 * "not implemented" without calling Facebook: nothing can be posted or deleted yet.
 */
final class FacebookChannelAdapter implements SocialChannelAdapter
{
    public function __construct(private FacebookGraphClient $graph, private FacebookSettings $settings) {}

    public function channel(): SocialChannel
    {
        return SocialChannel::FACEBOOK;
    }

    /**
     * Ask Facebook whether the stored page token still works for the stored page. The account is not modified here.
     * Success data: name, link, picture_url, expires_at (unix time or null = no expiry), scopes.
     */
    public function validateConnection(SocialAccount $account): SocialOperationResult
    {
        $token = ((array) $account->credentials)['page_access_token'] ?? null;
        if (! is_string($token) || $token === '' || blank($account->external_account_id)) {
            return SocialOperationResult::failed('no_token', __('admin.social.fb_api_no_token'));
        }

        // Permissions stored at connection time; used only when debug_token lists no scopes for the page token.
        $stored = array_values(array_filter(
            (array) (((array) $account->credentials)['granted_permissions'] ?? []),
            'is_string',
        ));

        try {
            $verified = $this->verifyPage((string) $account->external_account_id, $token, $stored);
        } catch (FacebookApiException $e) {
            return SocialOperationResult::failed($e->kind, $e->userMessage());
        } catch (SocialException $e) {
            return SocialOperationResult::failed($e->errorCode, $e->getMessage());
        }

        return SocialOperationResult::ok($verified['page']['id'], $verified['page']['link'], [
            'name' => $verified['page']['name'],
            'picture_url' => $verified['page']['picture_url'],
            'expires_at' => $verified['expires_at'],
            'scopes' => $verified['scopes'],
        ]);
    }

    /**
     * Confirm with Facebook that a page token belongs to this app, is valid, is the token of that page and carries the
     * permissions Phase 2A needs. Shared by the confirmation of a new connection and by validateConnection().
     *
     * @param  string[]  $fallbackScopes  permissions already verified for the person, used only when Meta lists none for the token
     * @return array{page: array<string, mixed>, expires_at: ?int, scopes: string[]}
     *
     * @throws FacebookApiException
     * @throws SocialException
     */
    public function verifyPage(string $pageId, string $pageToken, array $fallbackScopes): array
    {
        $page = $this->graph->page($pageId, $pageToken);
        if ($page['id'] !== $pageId) {
            throw new SocialException(__('admin.social.fb_error_page_mismatch'), 'page_mismatch');
        }

        $info = $this->graph->inspectToken($pageToken);
        if ((string) ($info['app_id'] ?? '') !== $this->settings->appId()) {
            throw new SocialException(__('admin.social.fb_error_app_mismatch'), 'app_mismatch');
        }
        if (empty($info['is_valid'])) {
            throw new FacebookApiException(FacebookApiException::AUTH_EXPIRED);
        }
        if (isset($info['profile_id']) && (string) $info['profile_id'] !== $pageId) {
            throw new SocialException(__('admin.social.fb_error_page_mismatch'), 'page_mismatch');
        }
        if (isset($info['type']) && strtoupper((string) $info['type']) !== 'PAGE') {
            throw new SocialException(__('admin.social.fb_error_token_not_page'), 'token_not_page');
        }

        $scopes = array_values(array_map('strval', (array) ($info['scopes'] ?? [])));
        if ($scopes === []) {
            $scopes = $fallbackScopes;
        }
        $missing = array_values(array_diff($this->settings->scopes(), $scopes));
        if ($missing !== []) {
            throw new SocialException(__('admin.social.fb_error_scopes_missing', ['permissions' => implode(', ', $missing)]), 'scopes_missing');
        }

        $expires = (int) ($info['expires_at'] ?? 0);

        return ['page' => $page, 'expires_at' => $expires > 0 ? $expires : null, 'scopes' => $scopes];
    }

    public function publish(SocialPublicationChannel $delivery, SocialAccount $account): SocialOperationResult
    {
        return $this->notImplemented();
    }

    public function remove(SocialPublicationChannel $delivery, SocialAccount $account): SocialOperationResult
    {
        return $this->notImplemented();
    }

    public function syncStatus(SocialPublicationChannel $delivery, SocialAccount $account): SocialOperationResult
    {
        return $this->notImplemented();
    }

    public function syncMetrics(SocialPublicationChannel $delivery, SocialAccount $account): SocialOperationResult
    {
        return $this->notImplemented();
    }

    private function notImplemented(): SocialOperationResult
    {
        return SocialOperationResult::failed('not_implemented', __('admin.social.error_not_implemented'));
    }
}
