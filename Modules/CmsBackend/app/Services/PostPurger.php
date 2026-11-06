<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class PostPurger
{
    public function __construct(private UploadReferences $uploads, private EditorialPolicy $policy, private EditorialWorkflow $workflow) {}

    /** @return array{removed: int, shared: int, failed: int} */
    public function execute(User $user, string $type, string $id, int $revision): array
    {
        $this->workflow->assertDatabase();
        $candidates = DB::transaction(function () use ($user, $type, $id, $revision): array {
            $post = Post::query()->with(['media', 'translations'])->where('post_type', $type)->lockForUpdate()->findOrFail($id);
            abort_unless($this->policy->allows($user, $post, 'force_delete'), 403);
            abort_unless((int) $this->workflow->meta($post)->revision === $revision, 409, __('admin.message.bai_da_thay_doi_hay_tai_lai_truoc_khi_xoa'));
            $linked = Post::query()->where('post_parent', $id)->exists()
                || DB::table('menu_items')->whereIn('type', ['post', 'page'])->where('ref_id', $id)->exists()
                || DB::table('posts_banners')->where('ref_id', $id)->where('post_id', '!=', $id)->exists();
            if ($linked) {
                throw ValidationException::withMessages(['confirmation' => __('admin.message.bai_dang_duoc_menu_banner_hoac_bai_khac_lien_ket')]);
            }
            $files = $this->uploads->inText($post->content) + $this->uploads->inText($post->excerpt) + $this->uploads->inText($post->render_file_in_view);
            foreach ($post->translations as $translation) {
                $files += $this->uploads->inText($translation->content)
                    + $this->uploads->inText($translation->excerpt)
                    + $this->uploads->inText($translation->seo_description);
            }
            foreach ($post->media as $media) {
                $files += $this->uploads->mediaFiles($media);
            }
            $this->deleteRecords([$post->id]);
            if (Schema::hasTable('activity_log')) {
                activity()->causedBy($user)->withProperties(['post_id' => $id, 'title' => $post->title])->log('Xóa vĩnh viễn bài và tập tin riêng');
            }

            return $files;
        });
        $referenced = $this->uploads->referenced();
        $result = ['removed' => 0, 'shared' => 0, 'failed' => 0];
        foreach ($candidates as $key => $file) {
            if (isset($referenced[$key])) {
                $result['shared']++;

                continue;
            }
            $file = $this->uploads->localFile($file);
            if ($file === null) {
                continue;
            }
            if (! @unlink($file)) {
                $result['failed']++;
                report(new \RuntimeException('Không xóa được tập tin riêng: '.$file));

                continue;
            }
            $result['removed']++;
            $this->uploads->removeEmptyParents($file);
        }

        return $result;
    }

    /** @param array<int, int|string> $ids */
    public function deleteRecords(array $ids): void
    {
        foreach (array_chunk($ids, 300) as $chunk) {
            foreach (['cms_editorial_meta', 'cms_editorial_history', 'comments', 'feedback', 'posts_banners', 'post_translations'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->whereIn('post_id', $chunk)->delete();
                }
            }
            DB::table('term_relationships')->where('object_type', (new Post)->getMorphClass())->whereIn('object_id', $chunk)->delete();
            if (Schema::hasTable('translations')) {
                DB::table('translations')->where('table_name', 'posts')->whereIn('object_id', $chunk)->delete();
            }
            DB::table('media')->where('model_type', (new Post)->getMorphClass())->whereIn('model_id', $chunk)->delete();
            Post::query()->whereIn('id', $chunk)->delete();
        }
    }
}
