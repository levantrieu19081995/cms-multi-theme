<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Console;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\CmsBackend\Support\ProjectContext;

/**
 * Assign existing posts to a project (table post_projects). Dry run unless --apply. Nothing else is written, and a post
 * that already belongs to another project is left alone unless --force. Projects are never guessed from titles.
 */
class AssignPostProject extends Command
{
    protected $signature = 'cms:project-assign
        {project? : Project key, as in config/projects.php}
        {--status : Show how many posts each project has, then stop}
        {--ids= : Comma separated post ids}
        {--category=* : Category slug (its posts and the posts of its sub-categories); repeatable}
        {--all-unassigned : Every post that belongs to no project yet}
        {--post-type=post : Post type to assign, or "all"}
        {--force : Move posts that already belong to another project}
        {--apply : Write the assignment (without it nothing is written)}';

    protected $description = 'Gán bài viết có sẵn cho một dự án (mặc định chỉ xem trước, thêm --apply để ghi).';

    public function handle(): int
    {
        if (! Schema::hasTable('post_projects')) {
            $this->error('Bảng post_projects chưa tồn tại. Hãy chạy migration trước.');

            return self::FAILURE;
        }
        if ($this->option('status')) {
            return $this->status();
        }

        $project = (string) $this->argument('project');
        if (! ProjectContext::exists($project)) {
            $this->error('Dự án "'.$project.'" chưa được khai báo. Dự án hợp lệ: '.implode(', ', array_keys(ProjectContext::projects())));

            return self::FAILURE;
        }

        $ids = $this->selectedIds();
        if ($ids === null) {
            $this->error('Hãy chọn bài cần gán: --ids=, --category= hoặc --all-unassigned.');

            return self::FAILURE;
        }

        $current = DB::table('post_projects')->whereIn('post_id', $ids)->pluck('project_key', 'post_id');
        $toWrite = [];
        $same = 0;
        $other = [];
        foreach ($ids as $id) {
            $owner = $current[$id] ?? null;
            if ($owner === $project) {
                $same++;
            } elseif ($owner === null || $this->option('force')) {
                $toWrite[] = $id;
                if ($owner !== null) {
                    $other[$owner] = ($other[$owner] ?? 0) + 1;
                }
            } else {
                $other[$owner] = ($other[$owner] ?? 0) + 1;
            }
        }
        $skipped = $this->option('force') ? 0 : array_sum($other);

        $this->table(['Mục', 'Số bài'], [
            ['Bài được chọn', count($ids)],
            ['Đã thuộc dự án '.$project, $same],
            ['Thuộc dự án khác'.($this->option('force') ? ' (sẽ chuyển)' : ' (bỏ qua)'), array_sum($other)],
            ['Sẽ ghi', count($toWrite)],
        ]);
        if ($skipped > 0) {
            $this->warn('Có bài đang thuộc dự án khác: '.json_encode($other).'. Dùng --force nếu thật sự muốn chuyển.');
        }

        if (! $this->option('apply')) {
            $this->info('Chế độ xem trước: chưa ghi gì. Thêm --apply để ghi.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($toWrite, $project) {
            foreach (array_chunk($toWrite, 500) as $chunk) {
                DB::table('post_projects')->upsert(
                    array_map(fn ($id) => ['post_id' => $id, 'project_key' => $project, 'created_at' => now(), 'updated_at' => now()], $chunk),
                    ['post_id'],
                    ['project_key', 'updated_at'],
                );
            }
        });
        $this->info('Đã gán '.count($toWrite).' bài cho dự án '.$project.'.');

        return self::SUCCESS;
    }

    /** @return int[]|null null when no selector was given */
    private function selectedIds(): ?array
    {
        $type = (string) $this->option('post-type');
        $ids = null;

        $explicit = trim((string) $this->option('ids'));
        if ($explicit !== '') {
            $ids = array_values(array_filter(array_map('intval', explode(',', $explicit))));
        }

        $slugs = array_filter((array) $this->option('category'));
        if ($slugs !== []) {
            $ids = array_merge($ids ?? [], $this->postIdsOfCategories($slugs));
        }

        if ($this->option('all-unassigned')) {
            $ids = array_merge($ids ?? [], DB::table('posts')->whereNull('deleted_at')
                ->whereNotIn('id', DB::table('post_projects')->select('post_id'))->pluck('id')->all());
        }

        if ($ids === null) {
            return null;
        }

        // Only existing, not deleted posts of the wanted type.
        $query = DB::table('posts')->whereNull('deleted_at')->whereIn('id', array_values(array_unique($ids)));
        if ($type !== 'all') {
            $query->where('post_type', $type);
        }

        return array_map('intval', $query->orderBy('id')->pluck('id')->all());
    }

    /** @param string[] $slugs */
    private function postIdsOfCategories(array $slugs): array
    {
        $taxonomies = DB::table('term_taxonomy as tt')->join('terms as t', 't.term_id', '=', 'tt.term_id')
            ->where('tt.taxonomy', 'category')->whereIn('t.slug', $slugs)->pluck('tt.term_taxonomy_id')->all();
        if ($taxonomies === []) {
            $this->warn('Không tìm thấy chuyên mục nào với slug: '.implode(', ', $slugs));

            return [];
        }

        // The category and every sub-category below it.
        $all = $taxonomies;
        $level = $taxonomies;
        while ($level !== []) {
            $level = array_diff(DB::table('term_taxonomy')->whereIn('parent', $level)->pluck('term_taxonomy_id')->all(), $all);
            $all = array_merge($all, $level);
        }

        return DB::table('term_relationships')->where('object_type', Post::class)
            ->whereIn('term_taxonomy_id', $all)->pluck('object_id')->map(fn ($id) => (int) $id)->all();
    }

    private function status(): int
    {
        $rows = DB::table('post_projects as pp')->join('posts as p', 'p.id', '=', 'pp.post_id')
            ->whereNull('p.deleted_at')->where('p.post_type', 'post')
            ->select('pp.project_key', DB::raw('COUNT(*) AS total'))->groupBy('pp.project_key')->pluck('total', 'project_key');
        $unassigned = DB::table('posts')->whereNull('deleted_at')->where('post_type', 'post')
            ->whereNotIn('id', DB::table('post_projects')->select('post_id'))->count();

        $table = [];
        foreach (ProjectContext::projects() as $key => $project) {
            $table[] = [$key, $project['label'], $rows[$key] ?? 0];
        }
        foreach ($rows as $key => $total) {
            if (! ProjectContext::exists((string) $key)) {
                $table[] = [$key, '(không khai báo)', $total];
            }
        }
        $table[] = ['—', 'Chưa gán dự án', $unassigned];
        $this->table(['Dự án', 'Tên', 'Số bài (post)'], $table);

        return self::SUCCESS;
    }
}
