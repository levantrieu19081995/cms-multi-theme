<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social;

/** Outcome of one adapter call. `message` is safe to show to a user (no token, secret or raw platform payload). */
final class SocialOperationResult
{
    /** @param array<string, mixed> $data metrics or status details chosen by the adapter */
    private function __construct(
        public readonly bool $success,
        public readonly ?string $remoteId = null,
        public readonly ?string $remoteUrl = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $message = null,
        public readonly array $data = [],
    ) {}

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
