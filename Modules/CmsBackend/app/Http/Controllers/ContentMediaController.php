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
}
