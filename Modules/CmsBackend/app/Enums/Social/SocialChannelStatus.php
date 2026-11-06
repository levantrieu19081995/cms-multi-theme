<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Enums\Social;

/** State of one delivery (one channel of one publication). `unavailable` = the channel has no connected account. */
enum SocialChannelStatus: string
{
    case DRAFT = 'draft';
    case UNAVAILABLE = 'unavailable';
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case PUBLISHED = 'published';
    case FAILED = 'failed';
    case REMOVED = 'removed';

    public function label(): string
    {
        return __('admin.social.channel_status_'.$this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-label-secondary',
            self::UNAVAILABLE => 'bg-label-warning',
            self::QUEUED, self::PROCESSING => 'bg-label-info',
            self::PUBLISHED => 'bg-label-success',
            self::FAILED => 'bg-label-danger',
            self::REMOVED => 'bg-label-dark',
        };
    }
}
