<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social\Facebook;

use RuntimeException;

/**
 * A failed Graph API call, reduced to what is safe to keep: a kind and Meta's error numbers. The text of the original
 * exception is dropped on purpose (a connection error message contains the request URL, which may carry a token).
 */
final class FacebookApiException extends RuntimeException
{
    public const NETWORK = 'network';

    public const AUTH_EXPIRED = 'auth_expired';

    public const AUTH_REVOKED = 'auth_revoked';

    public const PERMISSION = 'permission';

    public const RATE_LIMIT = 'rate_limit';

    public const INVALID = 'invalid_response';

    public const API = 'api';

    /** The picture could not be downloaded or is not a usable picture (before anything was sent to Meta). */
    public const IMAGE = 'image';

    /** The video could not be downloaded, is too large or is not a usable video (before anything was sent to Meta). */
    public const VIDEO = 'video';

    public function __construct(public readonly string $kind, public readonly ?int $metaCode = null, public readonly ?int $metaSubcode = null)
    {
        parent::__construct('Facebook API error ('.$kind.')');
    }

    /** Text for the administrator. Never contains a token, a secret or Meta's raw message. */
    public function userMessage(): string
    {
        // An error number this code does not map is still shown (numbers are safe), so it can be looked up.
        if ($this->kind === self::API && $this->metaCode !== null) {
            return __('admin.social.fb_api_api_code', ['code' => $this->metaCode.($this->metaSubcode !== null ? '/'.$this->metaSubcode : '')]);
        }

        return __('admin.social.fb_api_'.$this->kind);
    }

    public static function fromMetaError(array $error): self
    {
        $code = isset($error['code']) ? (int) $error['code'] : null;
        $sub = isset($error['error_subcode']) ? (int) $error['error_subcode'] : null;

        $kind = match (true) {
            $code === 190 && $sub === 458 => self::AUTH_REVOKED,
            $code === 190 => self::AUTH_EXPIRED,
            in_array($code, [4, 17, 32, 341, 613], true) => self::RATE_LIMIT,
            $code === 10 || ($code !== null && $code >= 200 && $code <= 299) => self::PERMISSION,
            default => self::API,
        };

        return new self($kind, $code, $sub);
    }
}
