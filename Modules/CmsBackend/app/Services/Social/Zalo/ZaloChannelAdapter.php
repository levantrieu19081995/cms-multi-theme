<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social\Zalo;

use Modules\CmsBackend\Enums\Social\SocialChannel;
use Modules\CmsBackend\Models\Social\SocialAccount;
use Modules\CmsBackend\Models\Social\SocialPublication;
use Modules\CmsBackend\Models\Social\SocialPublicationChannel;
use Modules\CmsBackend\Services\Social\SocialChannelAdapter;
use Modules\CmsBackend\Services\Social\SocialMedia;
use Modules\CmsBackend\Services\Social\SocialOperationResult;

/**
 * Zalo Official Account adapter. A publication is sent as an OA ARTICLE (article/create). Zalo builds an article asynchronously
 * and answers a progress token first; the article id comes from article/verify. When the id is not ready right after creation the
 * delivery stays "processing" with remote_post_id = "pending:<token>", and the next synchronization (syncStatus) turns it into the
 * real id. A pending article is never reported as a failure: it may exist on Zalo, so it must not be created a second time.
 *
 * Views and shares of an article, and the address to read it, come from the article list (article/getslice): syncMetrics() finds
 * the article in it by id. Zalo documents no likes or comments.
 */
final class ZaloChannelAdapter implements SocialChannelAdapter
{
    /** Prefix of remote_post_id while the article id is not known yet. */
    public const PENDING = 'pending:';

    private const TITLE_MAX = 150;

    private const AUTHOR_MAX = 50;

    private const DESCRIPTION_MAX = 300;

    /** Articles per page of the list, and the most pages read to find one (10 per page is Zalo's page size). */
    private const LIST_PAGE_SIZE = 10;

    private const LIST_PAGES = 20;

    public function __construct(
        private ZaloApiClient $client,
        private ZaloTokenService $tokens,
        private ZaloSettings $settings,
    ) {}

    public function channel(): SocialChannel
    {
        return SocialChannel::ZALO;
    }

    /**
     * Ask Zalo about the OA with the stored token (renewed first when needed). The account's tokens may be renewed and stored;
     * nothing else is modified here.
     * Success data: name, description, alias, is_verified, followers, avatar, cover, package, expires_at (refresh token expiry).
     */
    public function validateConnection(SocialAccount $account): SocialOperationResult
    {
        try {
            $oa = $this->withToken($account, fn (string $token) => $this->client->oaInfo($token));
        } catch (ZaloApiException $e) {
            return SocialOperationResult::failed($e->kind, $e->userMessage());
        }

        return SocialOperationResult::ok($oa['oa_id'], null, [
            'name' => $oa['name'],
            'description' => $oa['description'],
            'alias' => $oa['alias'],
            'is_verified' => $oa['is_verified'],
            'followers' => $oa['followers'],
            'avatar' => $oa['avatar'],
            'cover' => $oa['cover'],
            'package' => $oa['package'],
            'expires_at' => $account->token_expires_at?->getTimestamp(),
        ]);
    }

    public function publish(SocialPublicationChannel $delivery, SocialAccount $account): SocialOperationResult
    {
        $publication = $delivery->publication;
        if ($publication === null) {
            return SocialOperationResult::failed('invalid', __('admin.social.zalo_api_invalid_response'));
        }

        // Articles are built here from text and pictures only; a video is refused before anything is sent.
        if (SocialMedia::urls($publication->mediaItems(), SocialMedia::VIDEO) !== []) {
            return SocialOperationResult::failed('video_unsupported', __('admin.social.error_media_zalo_video'));
        }

        $payload = $this->article($publication, $account);
        try {
            $progress = $this->withToken($account, fn (string $token) => $this->client->createArticle($token, $payload));
        } catch (ZaloApiException $e) {
            // Nothing was created (the request itself was refused), so the delivery can safely be sent again.
            return SocialOperationResult::failed($e->kind, $e->userMessage());
        }

        // The article exists on Zalo from here on. Ask for its id a few times; whatever happens, it is never a failure.
        $delay = $this->settings->verifyDelayMs();
        for ($attempt = 1; $attempt <= $this->settings->verifyAttempts(); $attempt++) {
            try {
                $id = $this->withToken($account, fn (string $token) => $this->client->verifyArticle($token, $progress));
            } catch (ZaloApiException $e) {
                return SocialOperationResult::pending(self::PENDING.$progress, $e->userMessage());
            }
            if ($id !== null) {
                return SocialOperationResult::ok($id);
            }
            if ($delay > 0 && $attempt < $this->settings->verifyAttempts()) {
                usleep($delay * 1000);
            }
        }

        return SocialOperationResult::pending(self::PENDING.$progress, __('admin.social.zalo_article_processing'));
    }

    /** Delete the article (article/remove). An article whose id is not known yet cannot be deleted: synchronize first. */
    public function remove(SocialPublicationChannel $delivery, SocialAccount $account): SocialOperationResult
    {
        $articleId = (string) $delivery->remote_post_id;
        if ($articleId === '' || str_starts_with($articleId, self::PENDING)) {
            return SocialOperationResult::failed(ZaloApiException::PROCESSING, __('admin.social.zalo_remove_pending'));
        }

        try {
            $this->withToken($account, fn (string $token) => $this->client->removeArticle($token, $articleId));
        } catch (ZaloApiException $e) {
            return SocialOperationResult::failed($e->kind, $e->userMessage());
        }

        return SocialOperationResult::ok($articleId);
    }

    /**
     * A pending article: ask for its id (ok with the real id, or pending again). A known article: check that it can still be read.
     */
    public function syncStatus(SocialPublicationChannel $delivery, SocialAccount $account): SocialOperationResult
    {
        $remote = (string) $delivery->remote_post_id;
        if ($remote === '') {
            return SocialOperationResult::failed('no_remote_id', __('admin.social.zalo_api_invalid_response'));
        }

        try {
            if (str_starts_with($remote, self::PENDING)) {
                $id = $this->withToken($account, fn (string $token) => $this->client->verifyArticle($token, substr($remote, strlen(self::PENDING))));

                return $id === null
                    ? SocialOperationResult::pending($remote, __('admin.social.zalo_article_processing'))
                    : SocialOperationResult::ok($id);
            }
            $this->withToken($account, fn (string $token) => $this->client->articleDetail($token, $remote));
        } catch (ZaloApiException $e) {
            return SocialOperationResult::failed($e->kind, $e->userMessage());
        }

        return SocialOperationResult::ok($remote);
    }

    /**
     * Views and shares of the article, and its public address (`link_view`). The article is looked up in the list of the OA, newest
     * first; the most recent articles (the ones just published) are on the first page. An article that is not found in the pages
     * read has no figures yet: that is not an error (its existence is checked by syncStatus()).
     * Success data: views, shares. Success remoteUrl: link_view.
     */
    public function syncMetrics(SocialPublicationChannel $delivery, SocialAccount $account): SocialOperationResult
    {
        $remote = (string) $delivery->remote_post_id;
        if ($remote === '' || str_starts_with($remote, self::PENDING)) {
            return SocialOperationResult::ok($remote);
        }

        try {
            $article = $this->findInList($account, $remote);
        } catch (ZaloApiException $e) {
            return SocialOperationResult::failed($e->kind, $e->userMessage());
        }
        if ($article === null) {
            return SocialOperationResult::ok($remote);
        }

        return SocialOperationResult::ok($remote, $article['link'], ['views' => $article['views'], 'shares' => $article['shares']]);
    }

    // ------------------------------------------------------------------ internals

    /**
     * Run a call with a valid access token. A token Zalo refuses (-216 / -220) is renewed once and the call repeated: a refused
     * request was not executed, so repeating it cannot duplicate anything.
     *
     * @template T
     *
     * @param  \Closure(string): T  $call
     * @return T
     *
     * @throws ZaloApiException
     */
    private function withToken(SocialAccount $account, \Closure $call): mixed
    {
        $token = $this->tokens->accessToken($account);
        try {
            return $call($token);
        } catch (ZaloApiException $e) {
            if ($e->kind !== ZaloApiException::AUTH_EXPIRED) {
                throw $e;
            }

            return $call($this->tokens->accessToken($account, $token));
        }
    }

    /**
     * Look an article up in the list of the OA, page by page (newest first), stopping at the first page that holds it, at the end of
     * the list, or after a bounded number of pages.
     *
     * @return array{id: string, status: ?string, views: int, shares: int, link: ?string}|null
     *
     * @throws ZaloApiException
     */
    private function findInList(SocialAccount $account, string $articleId): ?array
    {
        for ($page = 0; $page < self::LIST_PAGES; $page++) {
            $offset = $page * self::LIST_PAGE_SIZE;
            $list = $this->withToken($account, fn (string $token) => $this->client->articleList($token, $offset, self::LIST_PAGE_SIZE));
            foreach ($list['items'] as $item) {
                if ($item['id'] === $articleId) {
                    return $item;
                }
            }
            if (count($list['items']) < self::LIST_PAGE_SIZE || $offset + self::LIST_PAGE_SIZE >= $list['total']) {
                break;
            }
        }

        return null;
    }

    /**
     * The article for a publication: title, the message and a "read more" line with the public address, and the image as cover.
     * Zalo limits: title 150, author 50, description 300 characters.
     *
     * @return array<string, mixed>
     */
    private function article(SocialPublication $publication, SocialAccount $account): array
    {
        $title = trim((string) $publication->title);
        $message = trim((string) $publication->message);
        $text = $message !== '' ? $message : $title;

        // A publication written directly in the CMS has no public address: no "read more" line then.
        $body = [['type' => 'text', 'content' => $text]];
        if (trim((string) $publication->public_url) !== '') {
            $body[] = ['type' => 'text', 'content' => __('admin.social.zalo_read_more').': '.$publication->public_url];
        }

        $article = [
            'type' => 'normal',
            'title' => mb_substr($title !== '' ? $title : $text, 0, self::TITLE_MAX),
            'author' => mb_substr(trim((string) $account->name) !== '' ? trim((string) $account->name) : $title, 0, self::AUTHOR_MAX),
            'description' => mb_substr($text, 0, self::DESCRIPTION_MAX),
            'body' => $body,
            'status' => 'show',
            'comment' => 'show',
        ];

        // The first picture is the cover; the others follow the text, in order.
        $images = array_values(array_filter(SocialMedia::urls($publication->mediaItems(), SocialMedia::IMAGE), fn (string $url) => str_starts_with($url, 'https://')));
        if ($images !== []) {
            $article['cover'] = ['cover_type' => 'photo', 'photo_url' => $images[0], 'status' => 'show'];
            foreach (array_slice($images, 1) as $url) {
                $article['body'][] = ['type' => 'image', 'url' => $url];
            }
        }

        return $article;
    }
}
