<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services;

use App\Models\Post;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\CmsFrontend\Http\Controllers\HomeController;

final class StarterContent
{
    /** @return array<int, int> */
    public function homepageIds(): array
    {
        $ids = [];
        $slugs = [];
        $walk = function (mixed $value) use (&$walk, &$ids, &$slugs): void {
            if ($value instanceof Post) {
                $ids[(int) $value->id] = true;
            } elseif ($value instanceof Collection) {
                foreach ($value as $item) {
                    $walk($item);
                }
            } elseif (is_array($value)) {
                if (isset($value['id'], $value['embed_url'])) {
                    $ids[(int) $value['id']] = true;
                }
                foreach ($value as $item) {
                    $walk($item);
                }
            } elseif (is_string($value)) {
                foreach ($this->slugs($value) as $slug) {
                    $slugs[$slug] = true;
                }
            }
        };
        $walk(app(HomeController::class)->index()->getData());
        foreach (Post::query()->whereIn('slug', array_keys($slugs))->pluck('id') as $id) {
            $ids[(int) $id] = true;
        }
        $result = array_keys($ids);
        sort($result);

        return $result;
    }

    /** @return array<int, string> */
    private function slugs(string $text): array
    {
        preg_match_all('~/([^/\s<>"\x27?#]+)\.htm(?:[?#\s<>"\x27]|$)~u', html_entity_decode(str_replace('\\/', '/', $text), ENT_QUOTES | ENT_HTML5, 'UTF-8'), $matches);

        return array_values(array_unique(array_map('rawurldecode', $matches[1])));
    }

    /** @return array{keep: array<int, int>, delete: array<int, int>, homepage: array<int, int>} */
    public function plan(string $createdBefore): array
    {
        $homepage = $this->homepageIds();
        $keep = [...$homepage,
            ...Post::query()->where('post_type', '!=', 'post')->pluck('id')->all(),
            ...Post::query()->where('created_at', '>=', $createdBefore)->pluck('id')->all(),
            ...DB::table('cms_editorial_history')->where('action', 'sample_content')->pluck('post_id')->all(),
            ...DB::table('menu_items')->whereIn('type', ['page', 'post'])->pluck('ref_id')->all(),
            ...DB::table('posts_banners')->where('redirect', 1)->pluck('ref_id')->all()];
        $globalText = DB::table('options')->pluck('value')->concat(DB::table('menu_items')->pluck('url'))->filter()->implode("\n");
        $keep = [...$keep, ...Post::query()->whereIn('slug', $this->slugs($globalText))->pluck('id')->all()];
        do {
            $before = count(array_unique($keep));
            $rows = Post::query()->whereIn('id', array_filter($keep))->get(['id', 'post_parent', 'content', 'excerpt']);
            $slugs = [];
            foreach ($rows as $post) {
                if ($post->post_parent) {
                    $keep[] = (int) $post->post_parent;
                }
                $slugs = [...$slugs, ...$this->slugs(($post->content ?? '')."\n".($post->excerpt ?? ''))];
            }
            $keep = array_values(array_unique(array_map('intval', [...$keep, ...Post::query()->whereIn('slug', $slugs)->pluck('id')->all()])));
        } while (count($keep) > $before);
        $keep = Post::query()->whereIn('id', $keep)->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $delete = Post::query()->where('post_type', 'post')->whereNotIn('id', $keep)->pluck('id')->map(fn ($id): int => (int) $id)->all();

        return compact('keep', 'delete', 'homepage');
    }
}
