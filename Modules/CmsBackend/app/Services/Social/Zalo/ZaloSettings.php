<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social\Zalo;

/** Zalo App settings from config('cmsbackend.social.zalo'). Values are read, never echoed to a view or a log. */
final class ZaloSettings
{
    public function appId(): string
    {
        return trim((string) config('cmsbackend.social.zalo.app_id'));
    }

    public function appSecret(): string
    {
        return trim((string) config('cmsbackend.social.zalo.app_secret'));
    }

    /** The only callback address the OAuth flow accepts (never taken from a request). */
    public function redirectUri(): string
    {
        return trim((string) config('cmsbackend.social.zalo.redirect_uri'));
    }

    public function authorizeUrl(): string
    {
        return (string) config('cmsbackend.social.zalo.authorize_url');
    }

    public function tokenUrl(): string
    {
        return (string) config('cmsbackend.social.zalo.token_url');
    }

    public function apiBase(): string
    {
        return rtrim((string) config('cmsbackend.social.zalo.api_base'), '/').'/';
    }

    public function refreshTokenMonths(): int
    {
        return max(1, (int) config('cmsbackend.social.zalo.refresh_token_months', 3));
    }

    /** Seconds before its expiry at which an access token is already renewed. */
    public function accessTokenMargin(): int
    {
        return max(0, (int) config('cmsbackend.social.zalo.access_token_margin', 300));
    }

    public function verifyAttempts(): int
    {
        return max(1, (int) config('cmsbackend.social.zalo.verify_attempts', 3));
    }

    public function verifyDelayMs(): int
    {
        return max(0, (int) config('cmsbackend.social.zalo.verify_delay_ms', 1000));
    }

    public function stateTtl(): int
    {
        return max(60, (int) config('cmsbackend.social.zalo.state_ttl', 600));
    }

    public function timeout(): int
    {
        return max(1, (int) config('cmsbackend.social.zalo.timeout', 15));
    }

    public function connectTimeout(): int
    {
        return max(1, (int) config('cmsbackend.social.zalo.connect_timeout', 5));
    }

    /** App id, secret and callback look right. When false no OAuth address is built and the adapter is not registered. */
    public function configured(): bool
    {
        return $this->problem() === null;
    }

    /** Lang key (under admin.social.) naming the first thing wrong with the configuration, or null. Never contains a value. */
    public function problem(): ?string
    {
        if (preg_match('/^\d{5,30}$/', $this->appId()) !== 1) {
            return 'zalo_config_app_id';
        }
        if ($this->appSecret() === '') {
            return 'zalo_config_app_secret';
        }

        return $this->redirectUriProblem();
    }

    /** Fixed URI from the environment; HTTPS except plain HTTP on localhost / 127.0.0.1; no credentials, query or fragment. */
    private function redirectUriProblem(): ?string
    {
        $uri = $this->redirectUri();
        $parts = parse_url($uri);
        if ($uri === '' || $parts === false || filter_var($uri, FILTER_VALIDATE_URL) === false) {
            return 'zalo_config_redirect_invalid';
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || ! in_array($scheme, ['https', 'http'], true)) {
            return 'zalo_config_redirect_invalid';
        }
        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            return 'zalo_config_redirect_invalid';
        }
        if ($scheme === 'http' && ! in_array($host, ['localhost', '127.0.0.1'], true)) {
            return 'zalo_config_redirect_https';
        }

        return null;
    }
}
