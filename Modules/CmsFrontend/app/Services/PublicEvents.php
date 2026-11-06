<?php

namespace Modules\CmsFrontend\Services;

use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;

final class PublicEvents
{
    public static function query(): Builder
    {
        return Post::with(['media', 'author', 'taxonomies.term'])->leftJoin('cms_editorial_meta as cms_event_meta', 'cms_event_meta.post_id', '=', 'posts.id')
            ->select('posts.*')
            ->selectRaw('COALESCE(cms_event_meta.event_starts_at, posts.published_at) AS event_starts_at')
            ->selectRaw('COALESCE(cms_event_meta.event_ends_at, posts.publication_date_expires) AS event_ends_at')
            ->where('posts.post_type', 'event')->where('posts.status', 'published')->whereNull('posts.deleted_at')
            ->whereDoesntHave('taxonomies', fn ($q) => $q->where('loai_chuyen_muc', 'noi_bo'))
            ->where(function ($q) {
                // Existing events retain their old start/end date semantics until edited through the CMS workflow.
                $q->whereNull('cms_event_meta.post_id')->orWhere(function ($managed) {
                    $managed->where('posts.published_at', '<=', now())->where(fn ($expires) => $expires->whereNull('posts.publication_date_expires')->orWhere('posts.publication_date_expires', '>=', now()));
                });
            });
    }

    public static function during(Builder $query, $start, $end): Builder
    {
        return $query->whereRaw('DATE(COALESCE(cms_event_meta.event_starts_at, posts.published_at)) <= ?', [$end])->whereRaw('DATE(COALESCE(cms_event_meta.event_ends_at, posts.publication_date_expires)) >= ?', [$start]);
    }

    public static function today()
    {
        return self::during(self::query(), today()->toDateString(), today()->toDateString())->orderBy('event_starts_at')->limit(10)->get();
    }
}
