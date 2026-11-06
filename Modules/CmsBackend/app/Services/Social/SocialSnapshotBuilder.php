<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social;

use App\Models\Post;
use App\Services\LocaleManager;
use Illuminate\Support\Str;

/**
 * Builds the text, image and public address a CMS post is shared with, in one language. The result is copied into the
 * publication: later edits of the post never change an existing publication.
 */
final class SocialSnapshotBuilder
{
    /** Length of the message made from the post body when the post has no excerpt. */
    private const MESSAGE_LIMIT = 300;

    public function __construct(private LocaleManager $locales) {}

    /**
     * @return array{title: string, message: string, image_url: ?string, public_url: string}
     *
     * @throws SocialException when the post has no version in the language
     */
    public function build(Post $post, string $locale): array
    {
        if (! $this->locales->isSupported($locale)) {
            throw new SocialException(__('admin.social.error_locale_invalid'), 'locale_invalid');
        }

        if ($this->locales->isDefault($locale)) {
            $title = (string) $post->title;
            $slug = (string) $post->slug;
            $excerpt = (string) $post->excerpt;
            $content = (string) $post->content;
            $route = 'cms.tin-tuc.show';
        } else {
            $translation = $post->translation($locale);
            if ($translation === null || blank($translation->title) || blank($translation->slug)) {
                throw new SocialException(__('admin.social.error_translation_missing', ['locale' => $locale]), 'translation_missing');
            }
            $title = (string) $translation->title;
            $slug = (string) $translation->slug;
            $excerpt = (string) $translation->excerpt;
            $content = (string) $translation->content;
            $route = 'cms.'.$locale.'.tin-tuc.show';
        }

        $message = $this->plainText($excerpt);
        if ($message === '') {
            $message = Str::limit($this->plainText($content), self::MESSAGE_LIMIT, '…', true);
        }

        return [
            'title' => $this->plainText($title),
            'message' => $message,
            'image_url' => $this->imageUrl($post),
            'public_url' => route($route, ['slug' => $slug], true),
        ];
    }

    /** Languages in which the post can be shared: the default one, and every other one that has a filled translation. */
    public function availableLocales(Post $post): array
    {
        $available = [];
        foreach (array_keys($this->locales->supported()) as $locale) {
            if ($this->locales->isDefault($locale)
                || (($translation = $post->translation($locale)) && filled($translation->title) && filled($translation->slug))) {
                $available[] = $locale;
            }
        }

        return $available;
    }

    /** Editor HTML -> safe single-line text: no tags, no scripts, entities decoded, whitespace collapsed. */
    public function plainText(?string $html): string
    {
        $text = (string) $html;
        $text = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', ' ', $text) ?? $text;
        $text = preg_replace('~</?(p|div|br|li|ul|ol|h[1-6]|tr|td|th|table|blockquote|figure|figcaption)\b[^>]*>~i', ' ', $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Entities such as &lt;script&gt; decode into tags: remove them again so no markup reaches the message.
        $text = strip_tags($text);
        $text = preg_replace('/[\p{Cc}\x{200B}-\x{200D}\x{FEFF}]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private function imageUrl(Post $post): ?string
    {
        $url = trim((string) $post->getFirstMediaUrl('avatars'));
        if ($url === '') {
            return null;
        }

        return str_starts_with($url, '/') ? url($url) : $url;
    }
}
