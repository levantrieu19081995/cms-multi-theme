<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Enums\Social;

/** Overall state of a publication. Phase 1 only moves between draft, ready and archived. */
enum SocialPublicationStatus: string
{
    case DRAFT = 'draft';
    case READY = 'ready';
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case PARTIAL = 'partial';
    case PUBLISHED = 'published';
    case FAILED = 'failed';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return __('admin.social.status_'.$this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-label-secondary',
            self::READY => 'bg-label-info',
            self::QUEUED, self::PROCESSING => 'bg-label-warning',
            self::PARTIAL => 'bg-label-warning',
            self::PUBLISHED => 'bg-label-success',
            self::FAILED => 'bg-label-danger',
            self::ARCHIVED => 'bg-label-dark',
        };
    }

    /** Content can still be edited: nothing has been sent to a channel. */
    public function editable(): bool
    {
        return in_array($this, [self::DRAFT, self::READY], true);
    }

    /** Statuses counted as "in progress" on the overview. */
    public static function inProgress(): array
    {
        return [self::QUEUED, self::PROCESSING];
    }
}
