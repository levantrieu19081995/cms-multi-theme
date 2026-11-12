<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Requests;

/**
 * A publication written directly (drawer of "Tạo bài đăng"): same fields as one made from a post, without the source post and the
 * language (the default language is used), plus the optional address of the page the post points to.
 */
class SocialDirectPublicationRequest extends SocialPublicationRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['post_id'], $rules['locale']);

        return $rules + [
            'idempotency_key' => ['required', 'string', 'regex:/^[A-Za-z0-9-]{16,64}$/'],
        ];
    }
}
