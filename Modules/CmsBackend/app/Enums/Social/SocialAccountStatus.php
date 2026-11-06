<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Enums\Social;

enum SocialAccountStatus: string
{
    case DISCONNECTED = 'disconnected';
    case CONNECTED = 'connected';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';
    case ERROR = 'error';

    public function label(): string
    {
        return __('admin.social.account_status_'.$this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::CONNECTED => 'bg-label-success',
            self::DISCONNECTED => 'bg-label-secondary',
            self::EXPIRED => 'bg-label-warning',
            self::REVOKED, self::ERROR => 'bg-label-danger',
        };
    }
}
