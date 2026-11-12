<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social\Facebook;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The only code that talks to Meta, always server to server over HTTPS with a timeout.
 *
 *  - A user or page token goes in the Authorization header, never in a URL or a log, together with appsecret_proof.
 *  - A failure is reduced to a FacebookApiException (kind + Meta error numbers). Raw responses and the text of connection
 *    errors are dropped: they can contain tokens or the request URL.
 *  - The log line of a failure holds the call name, HTTP status, Meta error code / subcode and fbtrace_id only.
 */
final class FacebookGraphClient
{
    private const GRAPH = 'https://graph.facebook.com/';

    /** Seconds allowed for uploading a video (up to 50 MB from the library) to Facebook. */
    private const VIDEO_TIMEOUT = 280;

    private const DIALOG = 'https://www.facebook.com/';

    private const PAGE_FIELDS = 'id,name,link,picture.type(large){url}';

    public function __construct(private FacebookSettings $settings) {}

    /** Address of the Meta login dialog. Only the fixed redirect address of the configuration is used. */
    public function authorizationUrl(string $state): string
    {
        return self::DIALOG.$this->settings->graphVersion().'/dialog/oauth?'.http_build_query([
            'client_id' => $this->settings->appId(),
            'redirect_uri' => $this->settings->redirectUri(),
            'state' => $state,
            'scope' => implode(',', $this->settings->scopes()),
            'response_type' => 'code',
            // Ask again for a permission the person declined earlier.
            'auth_type' => 'rerequest',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** Authorization code -> short-lived user access token. */
    public function exchangeCode(string $code): string
    {
        $json = $this->send('POST', 'oauth/access_token', [
            'client_id' => $this->settings->appId(),
            'client_secret' => $this->settings->appSecret(),
            'redirect_uri' => $this->settings->redirectUri(),
            'code' => $code,
        ], null, 'exchange_code');

        return $this->tokenFrom($json);
    }

    /** Short-lived user token -> long-lived one, so the page tokens taken from it do not expire after an hour. */
    public function extendUserToken(string $token): string
    {
        $json = $this->send('POST', 'oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->settings->appId(),
            'client_secret' => $this->settings->appSecret(),
            'fb_exchange_token' => $token,
        ], null, 'extend_token');

        return $this->tokenFrom($json);
    }

    /**
     * Facts about a token, read with the app token: app_id, is_valid, type, profile_id, user_id, expires_at, scopes.
     *
     * @return array<string, mixed>
     */
    public function inspectToken(string $token): array
    {
        $json = $this->send('GET', 'debug_token', ['input_token' => $token], $this->settings->appId().'|'.$this->settings->appSecret(), 'debug_token', false);
        $data = $json['data'] ?? null;
        if (! is_array($data) || isset($data['error'])) {
            throw new FacebookApiException(FacebookApiException::INVALID);
        }

        return $data;
    }

    /** @return string[] names of the permissions the person granted */
    public function grantedPermissions(string $userToken): array
    {
        $json = $this->send('GET', 'me/permissions', [], $userToken, 'permissions');

        return array_values(array_map(
            fn ($row) => (string) $row['permission'],
            array_filter((array) ($json['data'] ?? []), fn ($row) => is_array($row) && ($row['status'] ?? '') === 'granted' && isset($row['permission']))
        ));
    }

    /** @return array{id: string, name: string} */
    public function me(string $userToken): array
    {
        $json = $this->send('GET', 'me', ['fields' => 'id,name'], $userToken, 'me');
        if (empty($json['id'])) {
            throw new FacebookApiException(FacebookApiException::INVALID);
        }

        return ['id' => (string) $json['id'], 'name' => (string) ($json['name'] ?? '')];
    }

    /**
     * Pages the person manages, with the page access token of each. The tokens stay on the server.
     *
     * @return array<int, array{id: string, name: string, link: string, picture_url: ?string, access_token: ?string, tasks: string[]}>
     */
    public function managedPages(string $userToken): array
    {
        $pages = [];
        $after = null;
        for ($round = 0; $round < 5; $round++) {
            $params = ['fields' => 'id,name,link,tasks,access_token,picture.type(large){url}', 'limit' => 100];
            if ($after !== null) {
                $params['after'] = $after;
            }
            $json = $this->send('GET', 'me/accounts', $params, $userToken, 'pages');
            foreach ((array) ($json['data'] ?? []) as $row) {
                if (is_array($row) && ! empty($row['id'])) {
                    $pages[] = $this->normalizePage($row);
                }
            }
            $after = $json['paging']['cursors']['after'] ?? null;
            if (empty($json['paging']['next']) || $after === null) {
                break;
            }
        }

        return $pages;
    }

    /**
     * One page as its own token sees it.
     *
     * @return array{id: string, name: string, link: string, picture_url: ?string, access_token: ?string, tasks: string[]}
     */
    public function page(string $pageId, string $pageToken): array
    {
        $json = $this->send('GET', rawurlencode($pageId), ['fields' => self::PAGE_FIELDS], $pageToken, 'page');
        if (empty($json['id'])) {
            throw new FacebookApiException(FacebookApiException::INVALID);
        }

        return $this->normalizePage($json);
    }

    /**
     * Publish a link post on the Page feed (POST /{page-id}/feed with message + link, Page access token). The text goes in
     * `message`; the preview card (title, picture) is read by Facebook from the link itself.
     *
     * @return string the id of the new post ("{page-id}_{post-id}")
     */
    public function publishLinkPost(string $pageId, string $pageToken, string $message, string $link = ''): string
    {
        // A post written directly in the CMS has no address: it is then a plain text post.
        $params = ['message' => $message, 'published' => 'true'] + ($link !== '' ? ['link' => $link] : []);
        $json = $this->send('POST', rawurlencode($pageId).'/feed', $params, $pageToken, 'publish');
        $id = $json['id'] ?? null;
        if (! is_string($id) || $id === '') {
            throw new FacebookApiException(FacebookApiException::INVALID);
        }

        return $id;
    }

    /**
     * Publish a photo post on the Page (POST /{page-id}/photos, multipart: the picture as the `source` file and `message` as its
     * caption, Page access token). The picture is shown in full, so the text and the article address stay with the image. The
     * file is uploaded, not fetched by Facebook from a URL: that fails whenever Facebook's own fetcher cannot reach our site.
     *
     * @return string the id of the new post ("{page-id}_{post-id}"); the photo id when Meta does not give the post id
     */
    public function publishPhotoPost(string $pageId, string $pageToken, string $message, string $imageBytes, string $filename): string
    {
        $json = $this->send('POST', rawurlencode($pageId).'/photos', ['message' => $message, 'published' => 'true'], $pageToken, 'publish_photo', true, ['contents' => $imageBytes, 'filename' => $filename]);
        $id = $json['post_id'] ?? $json['id'] ?? null;
        if (! is_string($id) || $id === '') {
            throw new FacebookApiException(FacebookApiException::INVALID);
        }

        return $id;
    }

    /**
     * Upload a picture that is not shown on its own (POST /{page-id}/photos with published=false). Several of them are then attached
     * to one feed post with publishFeedWithPhotos().
     *
     * @return string the id of the photo
     */
    public function uploadUnpublishedPhoto(string $pageId, string $pageToken, string $imageBytes, string $filename): string
    {
        $json = $this->send('POST', rawurlencode($pageId).'/photos', ['published' => 'false'], $pageToken, 'upload_photo', true, ['contents' => $imageBytes, 'filename' => $filename]);
        $id = $json['id'] ?? null;
        if (! is_string($id) || $id === '') {
            throw new FacebookApiException(FacebookApiException::INVALID);
        }

        return $id;
    }

    /**
     * One feed post that shows several pictures uploaded before (POST /{page-id}/feed with attached_media).
     *
     * @param  list<string>  $photoIds
     * @return string the id of the new post
     */
    public function publishFeedWithPhotos(string $pageId, string $pageToken, string $message, array $photoIds): string
    {
        $params = ['message' => $message, 'published' => 'true'];
        foreach (array_values($photoIds) as $index => $photoId) {
            $params['attached_media['.$index.']'] = json_encode(['media_fbid' => $photoId], JSON_UNESCAPED_SLASHES);
        }
        $json = $this->send('POST', rawurlencode($pageId).'/feed', $params, $pageToken, 'publish_photos');
        $id = $json['id'] ?? null;
        if (! is_string($id) || $id === '') {
            throw new FacebookApiException(FacebookApiException::INVALID);
        }

        return $id;
    }

    /**
     * Publish a video on the Page (POST /{page-id}/videos, multipart: the file as `source`, the text as `description`). The file is
     * streamed from disk, never held in memory. Facebook processes the video afterwards; the id is available at once.
     *
     * @param  resource  $stream
     * @return string the id of the video
     */
    public function publishVideo(string $pageId, string $pageToken, string $description, $stream, string $filename): string
    {
        $json = $this->send('POST', rawurlencode($pageId).'/videos', ['description' => $description, 'published' => 'true'], $pageToken, 'publish_video', true, ['contents' => $stream, 'filename' => $filename], self::VIDEO_TIMEOUT);
        $id = $json['id'] ?? null;
        if (! is_string($id) || $id === '') {
            throw new FacebookApiException(FacebookApiException::INVALID);
        }

        return $id;
    }

    /** DELETE /{post-id}. Meta limits this call to some developers; a refusal arrives as a permission error. */
    public function deletePost(string $postId, string $pageToken): bool
    {
        $json = $this->send('DELETE', rawurlencode($postId), [], $pageToken, 'delete_post');

        return ($json['success'] ?? false) === true;
    }

    /**
     * Existence and address of a post.
     *
     * @return array{id: string, permalink_url: ?string, created_time: ?string}
     */
    public function post(string $postId, string $pageToken): array
    {
        $json = $this->send('GET', rawurlencode($postId), ['fields' => 'id,permalink_url,created_time'], $pageToken, 'post');
        if (empty($json['id'])) {
            throw new FacebookApiException(FacebookApiException::INVALID);
        }
        $url = $json['permalink_url'] ?? null;

        return [
            'id' => (string) $json['id'],
            // Only an https address reaches a view.
            'permalink_url' => is_string($url) && str_starts_with($url, 'https://') ? $url : null,
            'created_time' => isset($json['created_time']) ? (string) $json['created_time'] : null,
        ];
    }

    /**
     * Reactions, comments and shares of a post. `reach` is not read: Meta removed the post reach metrics from Page Insights.
     *
     * @return array{reactions: int, comments: int, shares: int}
     */
    public function postMetrics(string $postId, string $pageToken): array
    {
        // A video node (its id has no "page_post" form) has no `shares` field: asking for it would fail the whole call.
        $fields = 'reactions.summary(total_count).limit(0),comments.summary(total_count).limit(0)'.(str_contains($postId, '_') ? ',shares' : '');
        $json = $this->send('GET', rawurlencode($postId), ['fields' => $fields], $pageToken, 'post_metrics');

        return [
            'reactions' => max(0, (int) ($json['reactions']['summary']['total_count'] ?? 0)),
            'comments' => max(0, (int) ($json['comments']['summary']['total_count'] ?? 0)),
            'shares' => max(0, (int) ($json['shares']['count'] ?? 0)),
        ];
    }

    // ------------------------------------------------------------------ internals

    /** @return array{id: string, name: string, link: string, picture_url: ?string, access_token: ?string, tasks: string[]} */
    private function normalizePage(array $row): array
    {
        $id = (string) $row['id'];
        // Only https addresses reach a view (an address from outside is never rendered as javascript: or data:).
        $https = fn (mixed $url): ?string => is_string($url) && str_starts_with($url, 'https://') ? $url : null;

        return [
            'id' => $id,
            'name' => (string) ($row['name'] ?? ''),
            'link' => $https($row['link'] ?? null) ?? 'https://www.facebook.com/'.$id,
            'picture_url' => $https($row['picture']['data']['url'] ?? null),
            'access_token' => isset($row['access_token']) ? (string) $row['access_token'] : null,
            'tasks' => array_values(array_map('strval', (array) ($row['tasks'] ?? []))),
        ];
    }

    private function tokenFrom(array $json): string
    {
        $token = $json['access_token'] ?? null;
        if (! is_string($token) || $token === '') {
            throw new FacebookApiException(FacebookApiException::INVALID);
        }

        return $token;
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array{contents: string|resource, filename: string}|null  $file  sent as the multipart `source` part of a POST
     * @param  int|null  $timeout  seconds; the configured one when null (a video upload needs far longer)
     * @return array<string, mixed>
     *
     * @throws FacebookApiException
     */
    private function send(string $method, string $path, array $params, ?string $token, string $call, bool $withProof = true, ?array $file = null, ?int $timeout = null): array
    {
        $request = Http::baseUrl(self::GRAPH.$this->settings->graphVersion().'/')
            ->timeout($timeout ?? $this->settings->timeout())
            ->connectTimeout($this->settings->connectTimeout())
            ->acceptJson();

        if ($token !== null) {
            $request = $request->withToken($token);
            if ($withProof) {
                $params['appsecret_proof'] = hash_hmac('sha256', $token, $this->settings->appSecret());
            }
        }

        try {
            $response = match ($method) {
                'GET' => $request->get($path, $params),
                'DELETE' => $request->asForm()->delete($path, $params),
                default => $file !== null
                    ? $request->attach('source', $file['contents'], $file['filename'])->post($path, $params)
                    : $request->asForm()->post($path, $params),
            };
        } catch (ConnectionException) {
            $this->fail($call, new FacebookApiException(FacebookApiException::NETWORK), null, null);
        } catch (\Throwable) {
            $this->fail($call, new FacebookApiException(FacebookApiException::INVALID), null, null);
        }

        $json = $response->json();
        $error = is_array($json) && isset($json['error']) && is_array($json['error']) ? $json['error'] : null;
        if ($response->failed() || $error !== null) {
            $exception = $error !== null
                ? FacebookApiException::fromMetaError($error)
                : new FacebookApiException($response->status() === 429 ? FacebookApiException::RATE_LIMIT : FacebookApiException::API);
            $this->fail($call, $exception, $response->status(), $error['fbtrace_id'] ?? null);
        }
        if (! is_array($json)) {
            $this->fail($call, new FacebookApiException(FacebookApiException::INVALID), $response->status(), null);
        }

        return $json;
    }

    private function fail(string $call, FacebookApiException $exception, ?int $http, mixed $trace): never
    {
        Log::warning('Facebook Graph call failed', [
            'call' => $call,
            'kind' => $exception->kind,
            'http' => $http,
            'code' => $exception->metaCode,
            'subcode' => $exception->metaSubcode,
            'trace' => is_string($trace) ? $trace : null,
        ]);

        throw $exception;
    }
}
