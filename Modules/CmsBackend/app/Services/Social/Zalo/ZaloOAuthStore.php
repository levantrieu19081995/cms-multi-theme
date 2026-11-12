<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social\Zalo;

use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Modules\CmsBackend\Services\Social\SocialException;

/**
 * Server-side memory of one Zalo OAuth attempt. Nothing about it is trusted from the browser.
 *
 * `state` is random and used once: the session keeps the raw value, the cache keeps the encrypted payload (user, project, time,
 * the PKCE code_verifier) under its SHA-256. The code_verifier never leaves the server; only its SHA-256 challenge is sent to Zalo.
 */
final class ZaloOAuthStore
{
    private const SESSION_STATE = 'social.zalo.state';

    public function __construct(private ZaloSettings $settings) {}

    /** @return array{state: string, challenge: string} what to send to Zalo */
    public function begin(Session $session, User $user, string $projectKey): array
    {
        $state = Str::random(48);
        // 43 characters from the unreserved set, a new one for every request (Zalo's recommendation).
        $verifier = Str::random(43);

        $session->put(self::SESSION_STATE, $state);
        Cache::put($this->key($state), Crypt::encryptString(json_encode([
            'user_id' => $user->id,
            'project_key' => $projectKey,
            'created_at' => time(),
            'redirect' => $this->settings->redirectUri(),
            'verifier' => $verifier,
        ], JSON_THROW_ON_ERROR)), $this->settings->stateTtl());

        return ['state' => $state, 'challenge' => ZaloApiClient::challengeFor($verifier)];
    }

    /**
     * Validate and burn the state of a callback: it must be the one this browser session started, exist, be unused, unexpired,
     * and belong to this user and project.
     *
     * @return array<string, mixed> the payload given to begin()
     *
     * @throws SocialException
     */
    public function consume(Session $session, string $state, User $user, string $projectKey): array
    {
        $raw = (string) $session->pull(self::SESSION_STATE, '');
        if ($raw === '' || $state === '' || ! hash_equals($raw, $state)) {
            throw new SocialException(__('admin.social.zalo_error_state_invalid'), 'state_invalid');
        }
        $payload = $this->decode(Cache::pull($this->key($state)));
        if ($payload === null || (int) $payload['created_at'] + $this->settings->stateTtl() < time()) {
            // Used before, expired, or gone from the cache: either way it cannot be used again.
            throw new SocialException(__('admin.social.zalo_error_state_expired'), 'state_expired');
        }
        if ((int) $payload['user_id'] !== (int) $user->id || (string) $payload['project_key'] !== $projectKey
            || ! hash_equals((string) $payload['redirect'], $this->settings->redirectUri())) {
            throw new SocialException(__('admin.social.zalo_error_state_invalid'), 'state_invalid');
        }

        return $payload;
    }

    /** Forget a started attempt (the person refused, or the callback was incomplete). */
    public function discard(Session $session): void
    {
        $raw = (string) $session->pull(self::SESSION_STATE, '');
        if ($raw !== '') {
            Cache::forget($this->key($raw));
        }
    }

    private function key(string $raw): string
    {
        return 'social.zalo.state.'.hash('sha256', $raw);
    }

    /** @return array<string, mixed>|null */
    private function decode(mixed $cipher): ?array
    {
        if (! is_string($cipher)) {
            return null;
        }
        try {
            $data = json_decode(Crypt::decryptString($cipher), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        return is_array($data) ? $data : null;
    }
}
