<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social;

use RuntimeException;

/** A business rule of the social publication feature was not met. The message is already translated and safe to show. */
final class SocialException extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode = 'social_error')
    {
        parent::__construct($message);
    }

    public static function channelNotConnected(): self
    {
        return new self(__('admin.social.error_channel_not_connected'), 'channel_not_connected');
    }

    public static function forbidden(): self
    {
        return new self(__('admin.social.error_forbidden'), 'forbidden');
    }

    /** Post, publication and account must belong to the current project. */
    public static function projectMismatch(): self
    {
        return new self(__('admin.social.error_project_mismatch'), 'project_mismatch');
    }

    public static function notEditable(): self
    {
        return new self(__('admin.social.error_not_editable'), 'not_editable');
    }
}
