<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social;

use Modules\CmsBackend\Enums\Social\SocialChannel;

/**
 * Finds the adapter of a channel. Phase 1 registers none, so every channel answers "not connected" and nothing is ever sent.
 * Phase 2 registers an adapter (a class name resolved from the container) in CmsBackendServiceProvider.
 */
final class SocialChannelRegistry
{
    /** @var array<string, class-string<SocialChannelAdapter>> */
    private array $adapters = [];

    /** @param class-string<SocialChannelAdapter> $adapter */
    public function register(SocialChannel $channel, string $adapter): void
    {
        $this->adapters[$channel->value] = $adapter;
    }

    public function has(SocialChannel $channel): bool
    {
        return isset($this->adapters[$channel->value]);
    }

    public function find(SocialChannel $channel): ?SocialChannelAdapter
    {
        return $this->has($channel) ? app($this->adapters[$channel->value]) : null;
    }

    /** @throws SocialException when no adapter exists for the channel */
    public function resolve(SocialChannel $channel): SocialChannelAdapter
    {
        return $this->find($channel) ?? throw SocialException::channelNotConnected();
    }
}
