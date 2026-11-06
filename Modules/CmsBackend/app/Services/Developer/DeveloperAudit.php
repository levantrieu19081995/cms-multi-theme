<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Developer;

/** Audit rows of Developer Settings: the actor is the developer session, never a CMS user (even if one is signed in). */
final class DeveloperAudit
{
    public const ACTOR = 'developer_session';

    /** @param  array<string, mixed>  $properties */
    public static function record(array $properties, string $message): void
    {
        activity()
            ->causedByAnonymous()
            ->withProperties(['actor' => self::ACTOR] + $properties)
            ->log($message);
    }
}
