<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Copies the current content of posts, terms and menu items into the translation tables as locale `vi`
 * (plan: Phase 2). Source tables are only read, never changed; ids and relations are kept. Safe to re-run:
 * an object that already has a `vi` row is skipped. seo_title / seo_description stay null because `posts` has no such data.
 *
 * `down()` is intentionally empty: the tables are removed by the previous migration, and the source data was never touched.
 */
return new class extends Migration
{
    private const LOCALE = 'vi';

    public function up(): void
    {
        $now = now();

        // Posts, including soft-deleted ones, so restoring a post never leaves it without a translation.
        DB::table('posts')->orderBy('id')->chunkById(200, function ($posts) use ($now) {
            $taken = DB::table('post_translations')->where('locale', self::LOCALE)->whereIn('slug', $posts->pluck('slug')->filter()->all())->pluck('post_id', 'slug');
            $has = DB::table('post_translations')->where('locale', self::LOCALE)->whereIn('post_id', $posts->pluck('id')->all())->pluck('post_id')->flip();
            $rows = [];
            foreach ($posts as $post) {
                if ($has->has($post->id)) {
                    continue;
                }
                $rows[] = [
                    'post_id' => $post->id,
                    'locale' => self::LOCALE,
                    'title' => $post->title,
                    'slug' => $this->uniqueSlug($post->slug, $post->id, $taken),
                    'excerpt' => $post->excerpt,
                    'content' => $post->content,
                    'seo_title' => null,
                    'seo_description' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $rows !== [] && DB::table('post_translations')->insert($rows);
        }, 'id');

        DB::table('terms')->orderBy('term_id')->chunkById(200, function ($terms) use ($now) {
            $taken = DB::table('term_translations')->where('locale', self::LOCALE)->whereIn('slug', $terms->pluck('slug')->filter()->all())->pluck('term_id', 'slug');
            $has = DB::table('term_translations')->where('locale', self::LOCALE)->whereIn('term_id', $terms->pluck('term_id')->all())->pluck('term_id')->flip();
            $rows = [];
            foreach ($terms as $term) {
                if ($has->has($term->term_id)) {
                    continue;
                }
                $rows[] = [
                    'term_id' => $term->term_id,
                    'locale' => self::LOCALE,
                    'name' => $term->name,
                    'slug' => $this->uniqueSlug($term->slug, $term->term_id, $taken),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $rows !== [] && DB::table('term_translations')->insert($rows);
        }, 'term_id');

        // menu_items.url becomes menu_item_translations.custom_url.
        DB::table('menu_items')->orderBy('id')->chunkById(200, function ($items) use ($now) {
            $has = DB::table('menu_item_translations')->where('locale', self::LOCALE)->whereIn('menu_item_id', $items->pluck('id')->all())->pluck('menu_item_id')->flip();
            $rows = [];
            foreach ($items as $item) {
                if ($has->has($item->id)) {
                    continue;
                }
                $rows[] = [
                    'menu_item_id' => $item->id,
                    'locale' => self::LOCALE,
                    'title' => $item->title,
                    'custom_url' => $item->url,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $rows !== [] && DB::table('menu_item_translations')->insert($rows);
        }, 'id');
    }

    public function down(): void
    {
        // Nothing to undo here; see the class comment.
    }

    /**
     * unique(locale, slug) must hold, but nothing guaranteed unique slugs in the old tables. The first holder keeps
     * its slug; a later duplicate gets "-{id}" appended (never dropped or merged). Empty slugs stay null.
     *
     * @param  \Illuminate\Support\Collection<string, int|string>  $taken  slug => owner id, shared across the chunk
     */
    private function uniqueSlug(?string $slug, int|string $id, $taken): ?string
    {
        $slug = trim((string) $slug);
        if ($slug === '') {
            return null;
        }
        $candidate = $slug;
        if ($taken->has($candidate) && (string) $taken->get($candidate) !== (string) $id) {
            $candidate = mb_substr($slug, 0, 240).'-'.$id;
        }
        $taken->put($candidate, $id);

        return $candidate;
    }
};
