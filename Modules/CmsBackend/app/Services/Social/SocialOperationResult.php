<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social;

/** Outcome of one adapter call. `message` is safe to show to a user (no token, secret or raw platform payload). */
final class SocialOperationResult
{
    /**
     * @param  array<string, mixed>  $data  metrics or status details chosen by the adapter
     * @param  bool  $pending  the platform accepted the request but has not finished it (Zalo processes an article asynchronously):
     *                         neither success nor failure; `remoteId` then holds what is needed to ask again
     */
    private function __construct(
        public readonly bool $success,
        public readonly ?string $remoteId = null,
        public readonly ?string $remoteUrl = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $message = null,
        public readonly array $data = [],
        public readonly bool $pending = false,
    ) {}

    /** Accepted, not finished. Never a failure: the thing may exist on the platform, so it must not be sent again. */
    public static function pending(string $remoteId, ?string $message = null): self
    {
        return new self(false, $remoteId, null, 'processing', $message, [], true);
    }

    /** @param array<string, mixed> $data */
    public static function ok(?string $remoteId = null, ?string $remoteUrl = null, array $data = []): self
    {
        return new self(true, $remoteId, $remoteUrl, null, null, $data);
    }

    public static function failed(string $errorCode, string $message): self
    {
        return new self(false, null, null, $errorCode, $message);
    }
}
