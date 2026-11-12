<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Requests;

use App\Services\LocaleManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\CmsBackend\Enums\Social\SocialChannel;

/**
 * Create and edit of a social publication. The permission is checked by the route middleware and again by
 * the service. A new publication also carries the source post, the language and the idempotency key of the form.
 */
class SocialPublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'image_url' => ['nullable', 'string', 'max:2000', function (string $attribute, mixed $value, \Closure $fail) {
                if (is_string($value) && $value !== '' && preg_match('~^(https?://|/(?!/))~i', $value) !== 1) {
                    $fail(__('admin.social.error_image_invalid'));
                }
            }],
            // Pictures / video picked in the library (the form always sends one empty entry, so "nothing picked" clears the list).
            // Kinds and counts are checked by the service, which reads the kind of each address from its extension.
            'media' => ['nullable', 'array', 'max:50'],
            'media.*' => ['nullable', 'string', 'max:2000'],
            // Only used by a publication written directly in the CMS; the address of a post-based one is fixed (the service ignores it).
            'public_url' => ['nullable', 'string', 'max:2000', function (string $attribute, mixed $value, \Closure $fail) {
                if (is_string($value) && trim($value) !== '' && preg_match('~^https?://[^\s]+$~i', trim($value)) !== 1) {
                    $fail(__('admin.social.error_url_invalid'));
                }
            }],
            'channels' => ['nullable', 'array'],
            'channels.*' => ['string', Rule::in(SocialChannel::values())],
            'intent' => ['required', Rule::in(['draft', 'ready'])],
        ];

        // No {id} in the route = a new publication (the production edge rejects method-override, so edit is a POST too).
        if ($this->route('id') === null) {
            $rules += [
                'post_id' => ['required', 'integer', 'min:1'],
                'locale' => ['required', 'string', Rule::in(array_keys(app(LocaleManager::class)->supported()))],
                'idempotency_key' => ['required', 'string', 'regex:/^[A-Za-z0-9-]{16,64}$/'],
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'title' => __('admin.social.field_title'),
            'message' => __('admin.social.field_message'),
            'image_url' => __('admin.social.field_image'),
            'channels' => __('admin.social.field_channels'),
            'locale' => __('admin.social.field_locale'),
        ];
    }
}
