<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social\Zalo;

use RuntimeException;

/**
 * A failed Zalo call, reduced to what is safe to keep: a kind and Zalo's error number. The text of Zalo's answer and of
 * connection errors is dropped on purpose (it can echo a token or the request address).
 *
 * Numbers (Zalo error table): -216 access token invalid, -220 access token expired or unavailable, -214 article still being
 * processed, -223 no API permission or article quota exceeded, -221 OA not verified for the feature, -211 feature quota
 * exceeded, -32 rate limit, -224 the OA package (tier) does not include the feature (community answers: "The OA needs to upgrade OA
 * Tier Package to use this feature"). Any other number is shown to the administrator as "Zalo error <number>".
 */
final class ZaloApiException extends RuntimeException
{
    public const NETWORK = 'network';

    public const AUTH_EXPIRED = 'auth_expired';

    public const PROCESSING = 'processing';

    public const PERMISSION = 'permission';

    public const NOT_VERIFIED = 'not_verified';

    public const QUOTA = 'quota';

    public const PLAN = 'plan';

    public const RATE_LIMIT = 'rate_limit';

    public const INVALID = 'invalid_response';

    public const API = 'api';

    public function __construct(public readonly string $kind, public readonly ?int $zaloCode = null)
    {
        parent::__construct('Zalo API error ('.$kind.')');
    }

    /** Text for the administrator. Never contains a token, a secret or Zalo's raw message. */
    public function userMessage(): string
    {
        // An error number this code does not know is still shown (a number is safe), so the administrator can look it up.
        if ($this->kind === self::API && $this->zaloCode !== null) {
            return __('admin.social.zalo_api_api_code', ['code' => $this->zaloCode]);
        }

        return __('admin.social.zalo_api_'.$this->kind);
    }

    public static function fromCode(int $code): self
    {
        $kind = match ($code) {
            -216, -220 => self::AUTH_EXPIRED,
            -214 => self::PROCESSING,
            -223 => self::PERMISSION,
            -221 => self::NOT_VERIFIED,
            -211 => self::QUOTA,
            -224 => self::PLAN,
            -32 => self::RATE_LIMIT,
            default => self::API,
        };

        return new self($kind, $code);
    }
}
