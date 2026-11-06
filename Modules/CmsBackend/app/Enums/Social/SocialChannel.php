<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Enums\Social;

/** Channels a publication can be sent to. The value is stored in the database and used as the platform of an account. */
enum SocialChannel: string
{
    case FACEBOOK = 'facebook';
    case ZALO = 'zalo';

    public function label(): string
    {
        return __('admin.social.channel_'.$this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::FACEBOOK => 'ri-facebook-circle-fill',
            self::ZALO => 'ri-chat-smile-3-line',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(fn (self $channel) => $channel->value, self::cases());
    }
}
