<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Developer;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Hash;
use Throwable;

/**
 * Developer Settings is separate from the CMS sign-in. It is guarded only by the bcrypt hash in .env
 * (DEVELOPER_ACCESS_TOKEN_HASH) and by a flag in the session ("developer_unlocked"). There is no user, no role and no
 * table involved, and the flag grants nothing outside the Developer Settings routes.
 */
final class DeveloperAccess
{
    public const SESSION_KEY = 'developer_unlocked';

    /** The feature exists only when a hash is configured. */
    public function enabled(): bool
    {
        return $this->hasToken();
    }

    public function hasToken(): bool
    {
        return trim((string) config('cmsbackend.developer.token_hash')) !== '';
    }

    /** Hash::check() against the configured hash. A value that is not a valid bcrypt hash never matches. */
    public function verifyToken(string $password): bool
    {
        $hash = trim((string) config('cmsbackend.developer.token_hash'));
        if ($hash === '' || $password === '') {
            return false;
        }
        try {
            return Hash::check($password, $hash);
        } catch (Throwable) {
            return false;
        }
    }

    /** A new session id first (against fixation), then the flag. */
    public function unlock(Session $session): void
    {
        $session->regenerate();
        $session->put(self::SESSION_KEY, true);
    }

    public function lock(Session $session): void
    {
        $session->forget(self::SESSION_KEY);
    }

    public function unlocked(Session $session): bool
    {
        return $session->get(self::SESSION_KEY) === true;
    }
}
