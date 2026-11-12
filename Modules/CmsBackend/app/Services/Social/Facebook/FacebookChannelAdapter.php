<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social\Facebook;

use Modules\CmsBackend\Enums\Social\SocialChannel;
use Modules\CmsBackend\Models\Social\SocialAccount;
use Modules\CmsBackend\Models\Social\SocialPublicationChannel;
use Modules\CmsBackend\Services\Social\SocialChannelAdapter;
use Modules\CmsBackend\Services\Social\SocialException;
use Modules\CmsBackend\Services\Social\SocialMedia;
use Modules\CmsBackend\Services\Social\SocialOperationResult;

/**
 * Facebook Page adapter: connection check (validateConnection), link posts on the Page feed (publish), deletion (remove), and
 * read-back of a post (syncStatus) and of its reactions, comments and shares (syncMetrics). Every call goes through
 * FacebookGraphClient with the Page access token; nothing here puts a token in a result or a message.
 */
final class FacebookChannelAdapter implements SocialChannelAdapter
{
    public function __construct(private FacebookGraphClient $graph, private FacebookSettings $settings, private FacebookImageFetcher $images) {}

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

    /**
     * Post the snapshot of the publication on the Page. With an https picture it is one photo post: the picture shown in full and,
     * as its caption, the title, the excerpt (the message) and the public address of the article, one per paragraph. Without a
     * picture it is a link post (title and excerpt as text; Facebook builds the preview card from the link).
     * Success: remoteId = Facebook post id.
     */
    public function publish(SocialPublicationChannel $delivery, SocialAccount $account): SocialOperationResult
    {
        $publication = $delivery->publication;
        $token = $this->pageToken($account);
        if ($publication === null || $token === null) {
            return SocialOperationResult::failed('no_token', __('admin.social.fb_api_no_token'));
        }

        $title = trim((string) $publication->title);
        $message = trim((string) $publication->message);
        // Title, then the excerpt; only one of them when the other is empty or the same.
        $text = $title === '' || $title === $message ? ($message !== '' ? $message : $title) : ($message === '' ? $title : $title."\n\n".$message);
        // Empty for a publication written directly in the CMS (no article): the post then carries no address.
        $link = trim((string) $publication->public_url);
        $caption = $link === '' ? $text : $text."\n\n".$link;
        $pageId = (string) $account->external_account_id;

        $items = $publication->mediaItems();
        $videos = SocialMedia::urls($items, SocialMedia::VIDEO);
        // Facebook is never asked to fetch a file: the CMS downloads it, so only https addresses of public hosts can be used.
        $images = array_values(array_filter(SocialMedia::urls($items, SocialMedia::IMAGE), fn (string $url) => str_starts_with($url, 'https://')));
        $hosts = [(string) parse_url($link, PHP_URL_HOST), (string) parse_url((string) config('app.url'), PHP_URL_HOST)];

        try {
            if ($videos !== []) {
                $postId = $this->publishVideo($pageId, $token, $caption, $videos[0], $hosts);
                $url = 'https://www.facebook.com/'.rawurlencode($pageId).'/videos/'.rawurlencode($postId);
            } elseif (count($images) >= 2) {
                $postId = $this->publishPhotos($pageId, $token, $caption, $images, $hosts);
                $url = 'https://www.facebook.com/'.rawurlencode($postId);
            } elseif (count($images) === 1) {
                // One picture shown in full, the text and the address as its caption.
                $file = $this->images->fetch($images[0], ...$hosts);
                $postId = $this->graph->publishPhotoPost($pageId, $token, $caption, $file['bytes'], $file['filename']);
                $url = 'https://www.facebook.com/'.rawurlencode($postId);
            } else {
                $postId = $this->graph->publishLinkPost($pageId, $token, $text, $link);
                $url = 'https://www.facebook.com/'.rawurlencode($postId);
            }
        } catch (FacebookApiException $e) {
            return SocialOperationResult::failed($e->kind, $e->userMessage());
        }

        return SocialOperationResult::ok($postId, $url);
    }

    /**
     * Several pictures in one post: every picture is downloaded first (so a bad address fails before anything is sent), uploaded
     * unpublished, then attached to one feed post.
     *
     * @param  list<string>  $images
     * @param  string[]  $hosts
     */
    private function publishPhotos(string $pageId, string $token, string $caption, array $images, array $hosts): string
    {
        $files = array_map(fn (string $url) => $this->images->fetch($url, ...$hosts), $images);
        $ids = array_map(fn (array $file) => $this->graph->uploadUnpublishedPhoto($pageId, $token, $file['bytes'], $file['filename']), $files);

        return $this->graph->publishFeedWithPhotos($pageId, $token, $caption, $ids);
    }

    /** @param string[] $hosts */
    private function publishVideo(string $pageId, string $token, string $caption, string $url, array $hosts): string
    {
        $file = $this->images->fetchVideo($url, ...$hosts);
        $stream = fopen($file['path'], 'rb');
        try {
            if ($stream === false) {
                throw new FacebookApiException(FacebookApiException::VIDEO);
            }

            return $this->graph->publishVideo($pageId, $token, $caption, $stream, $file['filename']);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            @unlink($file['path']);
        }
    }

    /**
     * Delete the post from the Page. Meta lets only some developers do this through the API, so a refusal is reported with its
     * own message and the delivery stays published: the post is then removed on Facebook by hand.
     */
    public function remove(SocialPublicationChannel $delivery, SocialAccount $account): SocialOperationResult
    {
        $token = $this->pageToken($account);
        if ($token === null || blank($delivery->remote_post_id)) {
            return SocialOperationResult::failed('no_token', __('admin.social.fb_api_no_token'));
        }

        try {
            $deleted = $this->graph->deletePost((string) $delivery->remote_post_id, $token);
        } catch (FacebookApiException $e) {
            return SocialOperationResult::failed($e->kind, $e->kind === FacebookApiException::PERMISSION ? __('admin.social.fb_remove_not_allowed') : $e->userMessage());
        }

        return $deleted
            ? SocialOperationResult::ok((string) $delivery->remote_post_id)
            : SocialOperationResult::failed(FacebookApiException::API, __('admin.social.fb_api_api'));
    }

    /** Does the post still exist on Facebook? Success data: permalink_url, created_time. */
    public function syncStatus(SocialPublicationChannel $delivery, SocialAccount $account): SocialOperationResult
    {
        $token = $this->pageToken($account);
        if ($token === null || blank($delivery->remote_post_id)) {
            return SocialOperationResult::failed('no_token', __('admin.social.fb_api_no_token'));
        }

        try {
            $post = $this->graph->post((string) $delivery->remote_post_id, $token);
        } catch (FacebookApiException $e) {
            // Meta answers "invalid parameter" for a post that was deleted or cannot be read: say so instead of a bare API error.
            $missing = $e->kind === FacebookApiException::API && $e->metaCode === 100;

            return SocialOperationResult::failed($missing ? 'remote_missing' : $e->kind, $missing ? __('admin.social.fb_post_missing') : $e->userMessage());
        }

        return SocialOperationResult::ok($post['id'], $post['permalink_url'], ['created_time' => $post['created_time']]);
    }

    /** Success data: reactions, comments, shares (integers). Reach is not available: Meta removed it from Page Insights. */
    public function syncMetrics(SocialPublicationChannel $delivery, SocialAccount $account): SocialOperationResult
    {
        $token = $this->pageToken($account);
        if ($token === null || blank($delivery->remote_post_id)) {
            return SocialOperationResult::failed('no_token', __('admin.social.fb_api_no_token'));
        }

        try {
            $metrics = $this->graph->postMetrics((string) $delivery->remote_post_id, $token);
        } catch (FacebookApiException $e) {
            return SocialOperationResult::failed($e->kind, $e->userMessage());
        }

        return SocialOperationResult::ok((string) $delivery->remote_post_id, null, $metrics);
    }

    /** The Page access token of the account, or null when it holds none. */
    private function pageToken(SocialAccount $account): ?string
    {
        $token = ((array) $account->credentials)['page_access_token'] ?? null;

        return is_string($token) && $token !== '' && filled($account->external_account_id) ? $token : null;
    }
}
