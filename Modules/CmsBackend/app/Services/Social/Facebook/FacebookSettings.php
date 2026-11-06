<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social\Facebook;

/** Meta App settings from config('cmsbackend.social.facebook'). Values are read, never echoed to a view or a log. */
final class FacebookSettings
{
    public function appId(): string
    {
        return trim((string) config('cmsbackend.social.facebook.app_id'));
    }

    public function appSecret(): string
    {
        return trim((string) config('cmsbackend.social.facebook.app_secret'));
    }

    public function graphVersion(): string
    {
        return trim((string) config('cmsbackend.social.facebook.graph_version'));
    }

    /** The only callback address the OAuth flow accepts (never taken from a request). */
    public function redirectUri(): string
    {
        return trim((string) config('cmsbackend.social.facebook.redirect_uri'));
    }

    /** @return string[] */
    public function scopes(): array
    {
        return array_values((array) config('cmsbackend.social.facebook.scopes', []));
    }

    public function stateTtl(): int
    {
        return max(60, (int) config('cmsbackend.social.facebook.state_ttl', 600));
    }

    public function timeout(): int
    {
        return max(1, (int) config('cmsbackend.social.facebook.timeout', 15));
    }

    public function connectTimeout(): int
    {
        return max(1, (int) config('cmsbackend.social.facebook.connect_timeout', 5));
    }

    /** All four values exist and look right. When false no OAuth address is built and the adapter is not registered. */
    public function configured(): bool
    {
        return $this->problem() === null;
    }

    /**
     * Lang key (under admin.social.) naming the first thing wrong with the configuration, or null when it is valid.
     * Never contains a value, so it is safe to show.
     */
    public function problem(): ?string
    {
        if (preg_match('/^\d{5,30}$/', $this->appId()) !== 1) {
            return 'fb_config_app_id';
        }
        if ($this->appSecret() === '') {
            return 'fb_config_app_secret';
        }
        if (preg_match('/^v\d+\.\d+$/', $this->graphVersion()) !== 1) {
            return 'fb_config_graph_version';
        }

        return $this->redirectUriProblem();
    }

    /**
     * The callback is a fixed URI from the environment (never derived from a request). HTTPS is required, except plain HTTP
     * on localhost / 127.0.0.1 for local development. No credentials, query string or fragment are allowed in it.
     */
    private function redirectUriProblem(): ?string
    {
        $uri = $this->redirectUri();
        $parts = parse_url($uri);
        if ($uri === '' || $parts === false || filter_var($uri, FILTER_VALIDATE_URL) === false) {
            return 'fb_config_redirect_invalid';
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || ! in_array($scheme, ['https', 'http'], true)) {
            return 'fb_config_redirect_invalid';
        }
        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            return 'fb_config_redirect_invalid';
        }
        if ($scheme === 'http' && ! in_array($host, ['localhost', '127.0.0.1'], true)) {
            return 'fb_config_redirect_https';
        }

        return null;
    }
}
