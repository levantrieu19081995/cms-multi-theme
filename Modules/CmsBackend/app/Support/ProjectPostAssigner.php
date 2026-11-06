<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Support;

use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Gives a newly created post to the current project (side table post_projects). It must never break saving a post: before
 * the migration, or when the project cannot be resolved, it does nothing and logs a warning.
 */
final class ProjectPostAssigner
{
    private static ?bool $ready = null;

    public static function assign(Post $post): void
    {
        try {
            self::$ready ??= Schema::hasTable('post_projects');
            if (! self::$ready || ! $post->getKey()) {
                return;
            }
            DB::table('post_projects')->insertOrIgnore([
                'post_id' => $post->getKey(),
                'project_key' => ProjectContext::key(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Post #'.$post->getKey().' was not assigned to a project: '.$e->getMessage());
        }
    }
}
