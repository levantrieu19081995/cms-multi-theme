<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social\Facebook;

use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Modules\CmsBackend\Services\Social\SocialException;

/**
 * Server-side memory of one OAuth attempt. Nothing about it is trusted from the browser.
 *
 *  - state: random, used once. The session keeps the raw value, the cache keeps the encrypted payload under its SHA-256
 *    (user, project, time, expected page). It expires after the TTL.
 *  - selection: the pages (with their page access tokens) returned after the login. Encrypted in the cache, tied to the
 *    user and the project, reachable only through a random handle kept in the session; removed on confirmation or expiry.
 * A page access token is never put in a view, a URL or a form.
 */
final class FacebookOAuthStore
{
    private const SESSION_STATE = 'social.facebook.state';

    private const SESSION_SELECTION = 'social.facebook.selection';

    public function __construct(private FacebookSettings $settings) {}

    /**
     * @param  array{page_id: ?string, page_url: ?string, business_id: ?string}  $expected  hints to compare with, never credentials
     * @return string the raw state to send to Meta
     */
    public function begin(Session $session, User $user, string $projectKey, array $expected): string
    {
        $raw = Str::random(48);
        $payload = [
            'user_id' => $user->id,
            'project_key' => $projectKey,
            'created_at' => time(),
            'redirect' => $this->settings->redirectUri(),
            'expected' => $expected,
        ];
        $session->put(self::SESSION_STATE, $raw);
        $this->put('state', $raw, $payload);

        return $raw;
    }

    /**
     * Validate and burn the state of a callback: it must be the one this browser session started, exist, be unused,
     * unexpired, and belong to this user and project.
     *
     * @return array<string, mixed> the payload given to begin()
     *
     * @throws SocialException
     */
    public function consume(Session $session, string $state, User $user, string $projectKey): array
    {
        $raw = (string) $session->pull(self::SESSION_STATE, '');
        if ($raw === '' || $state === '' || ! hash_equals($raw, $state)) {
            throw new SocialException(__('admin.social.fb_error_state_invalid'), 'state_invalid');
        }
        $payload = $this->pull('state', $state);
        if ($payload === null) {
            // Used before, or already gone from the cache: either way it cannot be used again.
            throw new SocialException(__('admin.social.fb_error_state_expired'), 'state_expired');
        }
        if ((int) $payload['created_at'] + $this->settings->stateTtl() < time()) {
            throw new SocialException(__('admin.social.fb_error_state_expired'), 'state_expired');
        }
        if ((int) $payload['user_id'] !== (int) $user->id || (string) $payload['project_key'] !== $projectKey
            || ! hash_equals((string) $payload['redirect'], $this->settings->redirectUri())) {
            throw new SocialException(__('admin.social.fb_error_state_invalid'), 'state_invalid');
        }

        return $payload;
    }

    /** Forget a started attempt (user refused, or an error before the pages were read). */
    public function discard(Session $session): void
    {
        $raw = (string) $session->pull(self::SESSION_STATE, '');
        if ($raw !== '') {
            Cache::forget($this->key('state', $raw));
        }
    }

    /** @param array<string, mixed> $selection */
    public function putSelection(Session $session, array $selection): void
    {
        $this->forgetSelection($session);
        $handle = Str::random(48);
        $selection['created_at'] = time();
        $session->put(self::SESSION_SELECTION, $handle);
        $this->put('selection', $handle, $selection);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws SocialException when there is none, it expired, or it belongs to another user or project
     */
    public function selection(Session $session, User $user, string $projectKey): array
    {
        $handle = (string) $session->get(self::SESSION_SELECTION, '');
        $selection = $handle === '' ? null : $this->read('selection', $handle);
        if ($selection === null || (int) $selection['created_at'] + $this->settings->stateTtl() < time()) {
            $this->forgetSelection($session);
            throw new SocialException(__('admin.social.fb_error_selection_expired'), 'selection_expired');
        }
        if ((int) $selection['user_id'] !== (int) $user->id || (string) $selection['project_key'] !== $projectKey) {
            throw new SocialException(__('admin.social.fb_error_state_invalid'), 'state_invalid');
        }

        return $selection;
    }

    public function forgetSelection(Session $session): void
    {
        $handle = (string) $session->pull(self::SESSION_SELECTION, '');
        if ($handle !== '') {
            Cache::forget($this->key('selection', $handle));
        }
    }

    // ------------------------------------------------------------------ cache (encrypted, keyed by SHA-256)

    private function key(string $kind, string $raw): string
    {
        return 'social.facebook.'.$kind.'.'.hash('sha256', $raw);
    }

    /** @param array<string, mixed> $payload */
    private function put(string $kind, string $raw, array $payload): void
    {
        Cache::put($this->key($kind, $raw), Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)), $this->settings->stateTtl());
    }

    /** @return array<string, mixed>|null */
    private function read(string $kind, string $raw): ?array
    {
        return $this->decode(Cache::get($this->key($kind, $raw)));
    }

    /** Read and delete in one step (a one-time value). */
    private function pull(string $kind, string $raw): ?array
    {
        return $this->decode(Cache::pull($this->key($kind, $raw)));
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
