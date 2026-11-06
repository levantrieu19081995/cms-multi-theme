<?php

namespace App\Console\Commands;

use App\Enums\CommentStatus;
use App\Enums\PostFormat;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Enums\PublishType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\Term;
use App\Models\Taxonomy;
use App\Models\Post;
use App\Enums\Taxonomy as TaxonomyEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportSampleDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-sample-data-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->import();
    }

    private function import()
    {
        $sampleData = File::get(storage_path('app/sample_data.json'));
        $list = json_decode($sampleData);
        $this->line(count($list) . ' category');

        DB::transaction(function () use ($list) {
            foreach ($list as $category) {
                $this->line('Import category ' . $category->name);
                $this->line(' - ' . count($category->posts) . ' posts');

                // Import category
                $model = Term::where('name', $category->name)
                    ->whereHas('taxonomy', function ($builder) {
                        $builder->where('taxonomy', TaxonomyEnum::CATEGORY->value);
                    })
                    ->first();
                if (!$model) {
                    $model = new Term;
                    $model->name = $category->name;
                    $model->slug = Str::slug($category->name);

                        // Tạo term
                        $model->save();
                        // Tạo taxonomy
                        $taxonomy = Taxonomy::create([
                        'term_id' => $model->term_id,
                        'taxonomy' => TaxonomyEnum::CATEGORY->value,
                        'parent' => 0
                    ]);
                }
                $taxonomy = $model->taxonomy;

                // Import post
                foreach ($category->posts as $post) {
                    $postModel = new Post;
                    $postModel->fill([
                        'title' => $post->title,
                        'content' => $post->content,
                        'slug' => Str::slug($post->title),
                        'author_id' => 1,
                        'publisher_id' => 1,
                        'status' => PostStatus::PUBLISHED->value,
                        'publish_type' => PublishType::PUBLISH_NOW->value,
                        'approved_at' => Carbon::now(),
                        'comment_status' => CommentStatus::OPEN->value,
                        'post_type' => PostType::POST->value,
                        'format' => PostFormat::DEFAULT->value,
                        'excerpt' => isset($post->summary) ? $post->summary : '',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'deleted_at' => null,
                        'published_at' => Carbon::now(),
                        'publication_date_expires',
                        'show_home_page' => true,
                    ]);
                    $postModel->save();
                    if (isset($post->image) && $post->image) {
                        $postModel->addMediaFromUrl($post->image)->toMediaCollection('avatars');
                    }

                    $postModel->taxonomies()->attach($taxonomy);
                }
            }
        });
    }
}
