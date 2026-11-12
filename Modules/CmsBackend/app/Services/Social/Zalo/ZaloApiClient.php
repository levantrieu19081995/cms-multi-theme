<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social\Zalo;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The only code that talks to Zalo, always server to server over HTTPS with a timeout.
 *
 *  - The OA access token goes in the `access_token` header, the app secret in the `secret_key` header: never in a URL, a body of
 *    the OAuth requests, or a log.
 *  - A failure is reduced to a ZaloApiException (kind + Zalo error number). Raw answers and the text of connection errors are
 *    dropped: they can contain tokens or the request address.
 *  - The log line of a failure holds the call name, HTTP status, Zalo error number and Zalo's short error description (token
 *    redacted, 200 characters at most). Screens only ever show our own text for the kind (plus the number).
 *  - The OA API answers HTTP 200 with a numeric `error` field (0 = success); the OAuth endpoint answers the tokens or an error.
 */
final class ZaloApiClient
{
    public function __construct(private ZaloSettings $settings) {}

    /** Address of the Zalo permission page. `code_challenge` = Base64URL(SHA-256(code_verifier)), no padding. */
    public function authorizationUrl(string $state, string $codeChallenge): string
    {
        return $this->settings->authorizeUrl().'?'.http_build_query([
            'app_id' => $this->settings->appId(),
            'redirect_uri' => $this->settings->redirectUri(),
            'code_challenge' => $codeChallenge,
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** PKCE challenge of a verifier. */
    public static function challengeFor(string $codeVerifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }

    /**
     * Authorization code -> tokens. The code is valid once, for 10 minutes.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    public function exchangeCode(string $code, string $codeVerifier): array
    {
        return $this->tokens([
            'code' => $code,
            'app_id' => $this->settings->appId(),
            'grant_type' => 'authorization_code',
            'code_verifier' => $codeVerifier,
        ], 'exchange_code');
    }

    /**
     * Refresh token -> new tokens. The refresh token is single-use: the answer carries the next one, and both tokens must be
     * stored. The old access token stops working too.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    public function refresh(string $refreshToken): array
    {
        return $this->tokens([
            'refresh_token' => $refreshToken,
            'app_id' => $this->settings->appId(),
            'grant_type' => 'refresh_token',
        ], 'refresh');
    }

    /**
     * Facts about the Official Account (needs the "manage OA information" permission).
     *
     * @return array{oa_id: string, name: string, description: string, alias: ?string, is_verified: bool, followers: ?int, avatar: ?string, cover: ?string, package: ?string}
     */
    public function oaInfo(string $accessToken): array
    {
        $data = $this->api('GET', 'oa/getoa', $accessToken, [], 'oa_info')['data'] ?? null;
        $id = is_array($data) ? (string) ($data['oaid'] ?? $data['oa_id'] ?? '') : '';
        if ($id === '') {
            throw new ZaloApiException(ZaloApiException::INVALID);
        }
        // Only an https address reaches a view (an address from outside is never rendered as javascript: or data:).
        $https = fn (mixed $url): ?string => is_string($url) && str_starts_with($url, 'https://') ? $url : null;

        return [
            'oa_id' => $id,
            'name' => (string) ($data['name'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'alias' => isset($data['oa_alias']) ? (string) $data['oa_alias'] : null,
            'is_verified' => (bool) ($data['is_verified'] ?? false),
            'followers' => isset($data['num_follower']) ? (int) $data['num_follower'] : null,
            'avatar' => $https($data['avatar'] ?? null),
            'cover' => $https($data['cover'] ?? null),
            'package' => isset($data['package_name']) ? (string) $data['package_name'] : null,
        ];
    }

    /**
     * Create an article. Zalo processes it asynchronously and answers a progress token, not the article id.
     *
     * @param  array<string, mixed>  $article
     * @return string the progress token
     */
    public function createArticle(string $accessToken, array $article): string
    {
        $token = $this->api('POST', 'article/create', $accessToken, $article, 'article_create')['data']['token'] ?? null;
        if (! is_string($token) || $token === '') {
            throw new ZaloApiException(ZaloApiException::INVALID);
        }

        return $token;
    }

    /** @return string|null the article id, or null while Zalo is still processing it (error -214) */
    public function verifyArticle(string $accessToken, string $progressToken): ?string
    {
        try {
            $id = $this->api('POST', 'article/verify', $accessToken, ['token' => $progressToken], 'article_verify')['data']['id'] ?? null;
        } catch (ZaloApiException $e) {
            if ($e->kind === ZaloApiException::PROCESSING) {
                return null;
            }
            throw $e;
        }
        if (! is_string($id) && ! is_int($id)) {
            throw new ZaloApiException(ZaloApiException::INVALID);
        }

        return (string) $id;
    }

    /** The article exists and can be read. @return array<string, mixed> */
    public function articleDetail(string $accessToken, string $articleId): array
    {
        $data = $this->api('GET', 'article/getdetail', $accessToken, ['id' => $articleId], 'article_detail')['data'] ?? null;

        return is_array($data) ? $data : [];
    }

    /**
     * One page of the articles of the OA, newest first (article/getslice). Per article Zalo gives `total_view`, `total_share`,
     * `status`, `create_date`, `update_date` and `link_view` (the address to read the article).
     *
     * @return array{total: int, items: array<int, array{id: string, status: ?string, views: int, shares: int, link: ?string}>}
     */
    public function articleList(string $accessToken, int $offset, int $limit): array
    {
        $data = $this->api('GET', 'article/getslice', $accessToken, ['offset' => max(0, $offset), 'limit' => max(1, $limit), 'type' => 'normal'], 'article_list')['data'] ?? null;
        $rows = is_array($data) ? ($data['medias'] ?? $data['items'] ?? []) : [];

        $items = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (! is_array($row) || ! isset($row['id'])) {
                continue;
            }
            $link = $row['link_view'] ?? null;
            $items[] = [
                'id' => (string) $row['id'],
                'status' => isset($row['status']) ? (string) $row['status'] : null,
                'views' => max(0, (int) ($row['total_view'] ?? 0)),
                'shares' => max(0, (int) ($row['total_share'] ?? 0)),
                // Only an https address reaches a view.
                'link' => is_string($link) && str_starts_with($link, 'https://') ? $link : null,
            ];
        }

        return ['total' => is_array($data) ? max(0, (int) ($data['total'] ?? count($items))) : 0, 'items' => $items];
    }

    public function removeArticle(string $accessToken, string $articleId): void
    {
        $this->api('POST', 'article/remove', $accessToken, ['id' => $articleId], 'article_remove');
    }

    // ------------------------------------------------------------------ internals

    private function http(): PendingRequest
    {
        return Http::timeout($this->settings->timeout())->connectTimeout($this->settings->connectTimeout())->acceptJson();
    }

    /**
     * @param  array<string, string>  $form
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    private function tokens(array $form, string $call): array
    {
        try {
            $response = $this->http()->withHeaders(['secret_key' => $this->settings->appSecret()])->asForm()->post($this->settings->tokenUrl(), $form);
        } catch (ConnectionException) {
            $this->fail($call, new ZaloApiException(ZaloApiException::NETWORK), null);
        } catch (\Throwable) {
            $this->fail($call, new ZaloApiException(ZaloApiException::INVALID), null);
        }

        $json = $response->json();
        $access = is_array($json) ? ($json['access_token'] ?? null) : null;
        $refresh = is_array($json) ? ($json['refresh_token'] ?? null) : null;
        if (! $response->failed() && is_string($access) && $access !== '' && is_string($refresh) && $refresh !== '') {
            return ['access_token' => $access, 'refresh_token' => $refresh, 'expires_in' => max(60, (int) ($json['expires_in'] ?? 90000))];
        }

        // A refused code or refresh token (used, expired, revoked) means the connection must be made again; a server or rate
        // problem does not.
        $kind = match (true) {
            $response->status() === 429 => ZaloApiException::RATE_LIMIT,
            $response->serverError() => ZaloApiException::API,
            $response->failed() || (is_array($json) && (isset($json['error']) || isset($json['error_name']))) => ZaloApiException::AUTH_EXPIRED,
            default => ZaloApiException::INVALID,
        };
        $code = is_array($json) && isset($json['error']) && is_numeric($json['error']) ? (int) $json['error'] : null;
        $this->fail($call, new ZaloApiException($kind, $code), $response->status());
    }

    /**
     * @param  array<string, mixed>  $data  JSON body of a POST, query of a GET
     * @return array<string, mixed>
     */
    private function api(string $method, string $path, string $accessToken, array $data, string $call): array
    {
        $request = $this->http()->withHeaders(['access_token' => $accessToken]);
        $url = $this->settings->apiBase().$path;

        try {
            $response = $method === 'GET' ? $request->get($url, $data) : $request->asJson()->post($url, $data);
        } catch (ConnectionException) {
            $this->fail($call, new ZaloApiException(ZaloApiException::NETWORK), null);
        } catch (\Throwable) {
            $this->fail($call, new ZaloApiException(ZaloApiException::INVALID), null);
        }

        $json = $response->json();
        if (is_array($json) && isset($json['error']) && is_numeric($json['error']) && (int) $json['error'] !== 0) {
            $this->fail($call, ZaloApiException::fromCode((int) $json['error']), $response->status(), $this->detail($json, $accessToken));
        }
        if ($response->failed()) {
            $this->fail($call, new ZaloApiException($response->status() === 429 ? ZaloApiException::RATE_LIMIT : ZaloApiException::API), $response->status());
        }
        if (! is_array($json)) {
            $this->fail($call, new ZaloApiException(ZaloApiException::INVALID), $response->status());
        }

        return $json;
    }

    /**
     * Zalo's own description of an error (a short text such as "The OA needs to upgrade OA Tier Package"), for the log only: the
     * access token is replaced if Zalo echoes it, control characters are dropped and the length is limited. It is never shown
     * in a screen or stored with the delivery.
     *
     * @param  array<string, mixed>  $json
     */
    private function detail(array $json, string $accessToken): ?string
    {
        $text = $json['message'] ?? $json['error_name'] ?? null;
        if (! is_string($text) || trim($text) === '') {
            return null;
        }
        if ($accessToken !== '') {
            $text = str_replace($accessToken, '[token]', $text);
        }
        $text = (string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $text);

        return mb_substr(trim($text), 0, 200);
    }

    private function fail(string $call, ZaloApiException $exception, ?int $http, ?string $detail = null): never
    {
        Log::warning('Zalo API call failed', array_filter([
            'call' => $call, 'kind' => $exception->kind, 'http' => $http, 'code' => $exception->zaloCode, 'zalo_message' => $detail,
        ], fn (mixed $value) => $value !== null));

        throw $exception;
    }
}
