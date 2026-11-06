<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

/**
 * Image gallery of one post as JSON. The edit-page scripts (post.js, su-kien/create.js) fetch /media-list/{id}, which the
 * catch-all redirect in routes/web.php sends to /cms/media-list/{id}.
 */
class ContentMediaController extends Controller
{
    public function list(int $postId): JsonResponse
    {
        $items = Post::query()->findOrFail($postId)->getMedia('images');

        return response()->json($items->map(fn ($media) => [
            'url' => $media->getUrl(),
            'description' => $media->getCustomProperty('description') ?? '',
            'index' => $media->getCustomProperty('index') ?? 0,
        ])->sortBy('index')->values());
    }

    /**
     * Post summary for the Banner form: after a post is picked, the form reads media[0].original_url as the banner image
     * (avatar first, then the post's own images). Answers {code, data} like the endpoint this replaces.
     */
    public function detailBySlug(string $postSlug): JsonResponse
    {
        $post = Post::query()->where('slug', ltrim($postSlug, '/'))->whereNull('deleted_at')->first();
        if (! $post) {
            return response()->json(['code' => 404], 404);
        }
        $media = $post->getMedia('avatars')->concat($post->getMedia('images'))
            ->map(fn ($m) => ['id' => $m->id, 'collection' => $m->collection_name, 'original_url' => $m->getUrl()])->values();

        return response()->json(['code' => 200, 'data' => ['id' => $post->id, 'title' => $post->title, 'slug' => $post->slug, 'media' => $media]]);
    }
}
