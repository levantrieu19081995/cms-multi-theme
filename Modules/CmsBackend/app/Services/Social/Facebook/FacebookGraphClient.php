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
     * @return array<string, mixed>
     *
     * @throws FacebookApiException
     */
    private function send(string $method, string $path, array $params, ?string $token, string $call, bool $withProof = true): array
    {
        $request = Http::baseUrl(self::GRAPH.$this->settings->graphVersion().'/')
            ->timeout($this->settings->timeout())
            ->connectTimeout($this->settings->connectTimeout())
            ->acceptJson();

        if ($token !== null) {
            $request = $request->withToken($token);
            if ($withProof) {
                $params['appsecret_proof'] = hash_hmac('sha256', $token, $this->settings->appSecret());
            }
        }

        try {
            $response = $method === 'GET' ? $request->get($path, $params) : $request->asForm()->post($path, $params);
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
