<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Developer;

use Modules\CmsBackend\Support\ProjectContext;
use RuntimeException;

/**
 * Reads, verifies and writes the deployment's permission manifest (storage/app/developer/permission-manifest.json).
 * The file lists the permission slugs this deployment may use. It is signed with HMAC-SHA256 (APP_KEY) and
 * bound to one project key; it never contains a secret. Nothing here reads the database.
 */
final class PermissionManifest
{
    public const VERSION = 1;

    public const ABSENT = 'absent';

    public const VALID = 'valid';

    public const INVALID = 'invalid';

    /** Project the manifest belongs to: the fixed project of the deployment, else the default one (never follows the theme). */
    public function projectKey(): string
    {
        return ProjectContext::fixed()
            ? ProjectContext::key()
            : (string) config('cmsbackend.projects.default', 'khuyen-nong');
    }

    public function path(): string
    {
        return (string) config('cmsbackend.developer.manifest_path');
    }

    public function backupPath(): string
    {
        return $this->path().'.bak';
    }

    public function hasKey(): bool
    {
        return $this->signingKey() !== '';
    }

    /**
     * @return array{state: string, reason: ?string, allowed: ?string[], updated_at: ?string}
     */
    public function read(?string $file = null): array
    {
        $file ??= $this->path();
        if (! is_file($file)) {
            return ['state' => self::ABSENT, 'reason' => null, 'allowed' => null, 'updated_at' => null];
        }
        $bad = fn (string $reason): array => ['state' => self::INVALID, 'reason' => $reason, 'allowed' => null, 'updated_at' => null];

        if (! $this->hasKey()) {
            return $bad('no_key');
        }
        $raw = @file_get_contents($file);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (! is_array($data)) {
            return $bad('structure');
        }
        if (($data['version'] ?? null) !== self::VERSION
            || ! is_string($data['project_key'] ?? null)
            || ! is_array($data['allowed_permissions'] ?? null)
            || ! is_string($data['updated_at'] ?? null)
            || ! is_string($data['signature'] ?? null)
            || array_diff_key($data, array_flip(['version', 'project_key', 'allowed_permissions', 'updated_at', 'signature'])) !== []) {
            return $bad('structure');
        }
        foreach ($data['allowed_permissions'] as $slug) {
            if (! is_string($slug) || preg_match('/^[a-z0-9][a-z0-9._-]{0,120}$/D', $slug) !== 1) {
                return $bad('structure');
            }
        }
        if (! hash_equals($this->sign($data['project_key'], $data['allowed_permissions'], $data['updated_at']), $data['signature'])) {
            return $bad('signature');
        }
        if ($data['project_key'] !== $this->projectKey()) {
            return $bad('project');
        }

        return ['state' => self::VALID, 'reason' => null, 'allowed' => array_values(array_unique($data['allowed_permissions'])), 'updated_at' => $data['updated_at']];
    }

    /**
     * Write a new manifest. The previous file (when present) becomes the single backup. The new content goes to a temporary
     * file first and is renamed over the manifest, so a half written file can never be read.
     *
     * @param  string[]  $allowed
     */
    public function write(array $allowed): string
    {
        if (! $this->hasKey()) {
            throw new RuntimeException('manifest key is not configured');
        }
        $allowed = array_values(array_unique($allowed));
        sort($allowed);
        $updatedAt = now()->toIso8601String();
        $payload = [
            'version' => self::VERSION,
            'project_key' => $this->projectKey(),
            'allowed_permissions' => $allowed,
            'updated_at' => $updatedAt,
            'signature' => $this->sign($this->projectKey(), $allowed, $updatedAt),
        ];
        $this->replace(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n", $this->read()['state'] === self::VALID); // an unreadable current file never replaces a good backup

        return $updatedAt;
    }

    /** True when a backup file exists (it may still turn out to be invalid). */
    public function hasBackup(): bool
    {
        return is_file($this->backupPath());
    }

    public function readBackup(): array
    {
        return $this->read($this->backupPath());
    }

    /** Make the backup the current manifest; the manifest it replaces becomes the backup (restore can be undone). */
    public function restore(): void
    {
        $backup = $this->readBackup();
        if ($backup['state'] !== self::VALID) {
            throw new RuntimeException('backup is not valid');
        }
        $content = (string) file_get_contents($this->backupPath());
        $this->replace($content, $this->read()['state'] === self::VALID);
    }

    private function replace(string $content, bool $keepPrevious): void
    {
        $path = $this->path();
        $dir = dirname($path);
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException('cannot create the manifest folder');
        }
        $lock = fopen($path.'.lock', 'c');
        if ($lock === false || ! flock($lock, LOCK_EX)) {
            throw new RuntimeException('cannot lock the manifest');
        }
        $tmp = $path.'.tmp-'.bin2hex(random_bytes(6));
        try {
            if (file_put_contents($tmp, $content, LOCK_EX) === false) {
                throw new RuntimeException('cannot write the manifest');
            }
            $previous = is_file($path) ? (string) file_get_contents($path) : null;
            if (! @rename($tmp, $path)) {
                throw new RuntimeException('cannot replace the manifest');
            }
            if ($keepPrevious && $previous !== null) {
                $bak = $this->backupPath().'.tmp-'.bin2hex(random_bytes(6));
                if (file_put_contents($bak, $previous) !== false) {
                    @rename($bak, $this->backupPath());
                }
            }
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** The application key (APP_KEY) as raw bytes. It is never written to the manifest, a view or a log. */
    private function signingKey(): string
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = (string) base64_decode(substr($key, 7), true);
        }

        return $key;
    }

    /** @param  string[]  $allowed */
    private function sign(string $project, array $allowed, string $updatedAt): string
    {
        $allowed = array_values(array_unique($allowed));
        sort($allowed);
        $canonical = json_encode(['version' => self::VERSION, 'project_key' => $project, 'allowed_permissions' => $allowed, 'updated_at' => $updatedAt], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash_hmac('sha256', (string) $canonical, $this->signingKey());
    }
}
