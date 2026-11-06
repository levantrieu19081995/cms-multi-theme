<?php

namespace Modules\CmsBackend\Services;

use App\Enums\PostStatus as State;
use App\Enums\PublishType;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\CmsBackend\Models\EditorialMeta;
use Modules\CmsBackend\Support\PublicContent;

final class EditorialWorkflow
{
    public function __construct(private EditorialPolicy $policy) {}

    public function assertDatabase(): void
    {
        $connection = DB::connection();
        if ($connection->getDriverName() === 'sqlite') {
            abort_unless($connection->getDatabaseName() === ':memory:' && config('cmsbackend.editorial_database') === ':memory:', 503);

            return;
        }
        $actual = $connection->selectOne('SELECT DATABASE() AS db')->db;
        abort_unless($actual === config('cmsbackend.editorial_database') && $actual !== env('SOCTRANG_DB_DATABASE', 'g2b_cms_caodangsoctrang'), 503, __('admin.message.workflow_dedicated_database'));
    }

    public function meta(Post $post): EditorialMeta
    {
        $meta = $post->exists ? EditorialMeta::find($post->id) : null;
        if ($meta) {
            return $meta;
        }
        $meta = new EditorialMeta(['post_id' => (string) $post->id, 'revision' => 0]);
        if ($post->exists && $post->post_type === 'event') {
            $meta->event_starts_at = $post->published_at;
            $meta->event_ends_at = $post->publication_date_expires;
        }

        return $meta;
    }

    public function execute(User $user, string $type, Request $request, ?string $id = null): Post
    {
        abort_unless(in_array($type, ['post', 'event'], true), 404);
        $this->assertDatabase();
        // The bilingual form posts the default language as translations[vi][...]; the rules below know the top-level names.
        app(PostTranslationWriter::class)->useDefaultLocaleInput($request);

        return DB::transaction(function () use ($user, $type, $request, $id) {
            if ($id !== null) {
                $post = Post::where('post_type', $type)->whereNull('deleted_at')->lockForUpdate()->findOrFail($id);
            } else {
                abort_unless($this->policy->has($user, $type, 'create'), 403);
                // Required by the source MySQL schema; this setting never publishes a draft.
                $post = new Post(['post_type' => $type, 'author_id' => $user->id, 'status' => State::DRAFT->value, 'publish_type' => PublishType::PUBLISH_NOW->value]);
            }
            $action = (string) $request->input('action');
            $this->authorize($user, $post, $action, $id === null);
            $meta = $this->meta($post);
            if ($id !== null && (! $request->has('revision') || (string) $request->input('revision') !== (string) $meta->revision)) {
                abort(409, __('admin.message.bai_da_duoc_xu_ly_o_phien_khac_hay_tai_lai_truoc'));
            }
            $from = $id === null ? null : $post->status;
            $writesContent = in_array($action, ['save_draft', 'submit_for_approval', 'save_changes'], true)
                || ($action === 'approved' && $request->has('title'));
            if ($action === 'approved' && $writesContent) {
                abort_unless($this->policy->allows($user, $post, 'save_changes'), 403);
            }
            if ($writesContent) {
                $this->content($user, $post, $meta, $request, in_array($action, ['submit_for_approval', 'approved'], true));
            }
            if ($action === 'approved') {
                $this->validateReady($user, $post, $meta, $writesContent ? $request->input('category_ids', []) : null);
            }
            if ($action === 'reject') {
                $request->validate(['feedback' => 'required|string|max:2000']);
            }
            $meta->revision++;
            switch ($action) {
                case 'save_draft': $post->status = State::DRAFT->value;
                    break;
                case 'submit_for_approval': $post->status = State::PENDING_APPROVAL->value;
                    $post->approved_at = null;
                    $meta->approved_revision = null;
                    $meta->approved_by = null;
                    break;
                case 'approved': $post->status = State::APPROVED->value;
                    $post->approved_at = now();
                    $meta->approved_by = (string) $user->id;
                    $meta->approved_revision = $meta->revision;
                    break;
                case 'reject': $post->status = State::NOT_APPROVED->value;
                    $post->rejected_at = now();
                    $post->approved_at = null;
                    $meta->approved_by = null;
                    $meta->approved_revision = null;
                    break;
                case 'publish':
                    // Legacy approved records may have no CMS revision yet; they still require the approved state and publish permission.
                    if ($meta->exists && $meta->approved_revision !== $meta->revision - 1) {
                        abort(409, __('admin.message.noi_dung_da_thay_doi_can_phe_duyet_lai'));
                    }
                    $this->validateReady($user, $post, $meta);
                    $publication = $request->validate(['publish_type' => 'required|in:publish_now,schedule_publication', 'scheduled_at' => 'required_if:publish_type,schedule_publication|nullable|date', 'publication_date_expires' => 'nullable|date']);
                    $post->publish_type = $publication['publish_type'];
                    $post->published_at = $post->publish_type === PublishType::SCHEDULE_PUBLICATION->value ? Carbon::parse($publication['scheduled_at']) : now();
                    $post->publication_date_expires = empty($publication['publication_date_expires']) ? null : Carbon::parse($publication['publication_date_expires']);
                    if ($post->publication_date_expires && Carbon::parse($post->publication_date_expires)->lte(Carbon::parse($post->published_at))) {
                        throw ValidationException::withMessages(['publication_date_expires' => __('admin.message.expiry_after_publish')]);
                    }
                    $post->publisher_id = $user->id;
                    $post->status = State::PUBLISHED->value;
                    break;
                case 'stop_publishing': $post->status = State::STOP_PUBLISHING->value;
                    break;
                case 'delete': $post->deleted_at = now();
                    break;
            }
            $post->save();
            $meta->post_id = (string) $post->id;
            $meta->save();
            if ($writesContent) {
                $post->taxonomies()->sync(array_merge($request->input('category_ids', []), $request->input('tag_ids', [])));
                $this->media($post, $request);
                // Same transaction as the post row: vi is mirrored into post_translations, other languages live only there.
                app(PostTranslationWriter::class)->save($post, $request);
            }
            if ($action === 'save_changes' && $from === State::PUBLISHED->value) {
                // Truy vết sửa trực tiếp bài đang đăng (ngoài dòng lịch sử xử lý của chính bài).
                activity()->performedOn($post)->causedBy($user)->withProperties(['ip' => $request->ip(), 'post_id' => $post->id])->log('Sửa trực tiếp bài đang đăng #'.$post->id);
            }
            DB::table('cms_editorial_history')->insert(['post_id' => (string) $post->id, 'user_id' => (string) $user->id, 'action' => $action, 'from_status' => $from, 'to_status' => $post->status, 'revision' => $meta->revision, 'note' => $action === 'reject' ? $request->input('feedback') : null, 'created_at' => now()]);

            return $post;
        });
    }

    private function authorize(User $user, Post $post, string $action, bool $creating): void
    {
        if ($creating) {
            abort_unless(in_array($action, ['save_draft', 'submit_for_approval'], true) && $this->policy->has($user, $post->post_type, 'create'), 403);
        } else {
            abort_unless($this->policy->allows($user, $post, $action), 403, __('admin.message.ban_khong_co_quyen_thuc_hien_buoc_nay_voi_bai_hi'));
        }
    }

    private function content(User $user, Post $post, EditorialMeta $meta, Request $request, bool $ready): void
    {
        $event = $post->post_type === 'event';
        $data = $request->validate([
            'title' => 'required|string|max:255', 'slug' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9_-]+$/',
            'content' => ($ready ? 'required' : 'nullable').'|string',
            'excerpt' => ($ready ? 'required' : 'nullable').'|string|max:1000',
            'author_names' => (! $event && $ready ? 'required' : 'nullable').'|string|max:255',
            'category_ids' => ($ready ? 'required|min:1' : 'nullable').'|array', 'category_ids.*' => 'integer|distinct',
            'tag_ids' => 'nullable|array', 'tag_ids.*' => 'integer|distinct',
            'related_parties' => 'nullable|string|max:1000',
            'event_starts_at' => ($event && $ready ? 'required' : 'nullable').'|date',
            'event_ends_at' => ($event && $ready ? 'required' : 'nullable').'|date|after_or_equal:event_starts_at',
            'avatar' => 'nullable|url|max:2000', 'fileDinhKem' => 'nullable|json',
        ] + app(PostTranslationWriter::class)->rules(), [
            'required_with' => __('admin.message.nhap_tieu_de_cua_ngon_ngu_nay_khi_da_nhap_cac_tr'),
            'translations.*.slug.regex' => __('admin.message.duong_dan_chi_chua_chu_khong_dau_so_va_dau_gach_'),
        ]);
        $ids = array_map('intval', $data['category_ids'] ?? []);
        abort_unless(array_diff($ids, $this->policy->categoryIds($user)) === [], 403, __('admin.message.chuyen_muc_khong_thuoc_pham_vi_duoc_phan_cong'));
        $tagIds = array_map('intval', $data['tag_ids'] ?? []);
        $allowedTags = \App\Models\Taxonomy::where('taxonomy', \App\Enums\Taxonomy::POSTTAG->value)->pluck('term_taxonomy_id')->map(fn ($id) => (int) $id)->all();
        abort_unless(array_diff($tagIds, $allowedTags) === [], 403);
        $post->title = $data['title'];
        $slug = $data['slug'] ?? $post->slug ?: Str::slug($data['title']);
        if ($slug === '') {
            $slug = 'tin-'.Str::lower(Str::random(8));
        }
        $base = $slug;
        for ($i = 1; Post::where('slug', $slug)->when($post->exists, fn ($q) => $q->where('id', '!=', $post->id))->exists(); $i++) {
            $slug = $base.'-'.$i;
        }
        $post->slug = $slug;
        $post->content = PublicContent::forStorage($data['content'] ?? '');
        $post->excerpt = $data['excerpt'] ?? '';
        $post->author_names = $data['author_names'] ?? '';
        $post->show_home_page = $request->boolean('show_home_page');
        $post->comment_status = $request->boolean('allow_comments') ? 'published' : 'disabled';
        $post->format = \App\Enums\PostFormat::DEFAULT->value;
        $meta->related_parties = $data['related_parties'] ?? null;
        if ($event) {
            $meta->event_starts_at = empty($data['event_starts_at']) ? null : Carbon::parse($data['event_starts_at']);
            $meta->event_ends_at = empty($data['event_ends_at']) ? null : Carbon::parse($data['event_ends_at']);
        }
        if ($ready && trim(strip_tags($post->content)) === '') {
            throw ValidationException::withMessages(['content' => __('admin.message.nhap_noi_dung_truoc_khi_gui_hoac_phe_duyet')]);
        }
    }

    private function validateReady(User $user, Post $post, EditorialMeta $meta, ?array $categories = null): void
    {
        $ids = $post->taxonomies->where('taxonomy', 'category')->pluck('term_taxonomy_id')->map(fn ($id) => (int) $id)->all();
        // The approval form's new category values are checked by content(); otherwise validate the stored snapshot.
        if ($categories !== null) {
            $ids = array_map('intval', $categories);
        }
        abort_unless(array_diff($ids, $this->policy->categoryIds($user)) === [], 403);
        $data = ['title' => $post->title, 'content' => trim(strip_tags((string) $post->content)), 'excerpt' => $post->excerpt, 'category_ids' => $ids, 'author_names' => $post->author_names, 'event_starts_at' => $meta->event_starts_at, 'event_ends_at' => $meta->event_ends_at];
        Validator::make($data, ['title' => 'required|max:255', 'content' => 'required', 'excerpt' => 'required', 'category_ids' => 'required|array|min:1', 'author_names' => $post->post_type === 'post' ? 'required' : 'nullable', 'event_starts_at' => $post->post_type === 'event' ? 'required|date' : 'nullable', 'event_ends_at' => $post->post_type === 'event' ? 'required|date|after_or_equal:event_starts_at' : 'nullable'])->validate();
    }

    private function media(Post $post, Request $request): void
    {
        if ($request->filled('avatar') && $request->input('avatar') !== $post->getFirstMediaUrl('avatars')) {
            $this->localMedia($post, $request->input('avatar'), 'avatars', true);
        }
        if ($request->has('fileDinhKem')) {
            $urls = json_decode($request->input('fileDinhKem') ?: '[]', true);
            if (! is_array($urls) || array_filter($urls, fn ($url) => ! is_string($url))) {
                throw ValidationException::withMessages(['fileDinhKem' => __('admin.message.danh_sach_tap_tin_khong_hop_le')]);
            }
            $existing = $post->getMedia('fileDinhKem');
            $removed = $existing->filter(fn ($item) => ! in_array($item->getUrl(), $urls, true));
            foreach ($urls as $url) {
                if (! $existing->contains(fn ($item) => $item->getUrl() === $url)) {
                    $this->localMedia($post, $url, 'fileDinhKem');
                }
            }
            $this->removeMedia($removed);
        }
    }

    private function localMedia(Post $post, string $url, string $collection, bool $avatar = false): void
    {
        // The existing picker returns local userfiles/media URLs. Never fetch arbitrary network URLs or delete source uploads.
        $host = parse_url($url, PHP_URL_HOST);
        if ($host && ! in_array(strtolower($host), [strtolower(request()->getHost()), strtolower(parse_url(config('app.url'), PHP_URL_HOST) ?? '')], true)) {
            throw ValidationException::withMessages(['avatar' => __('admin.message.pick_file_from_library')]);
        }
        $path = rawurldecode(parse_url($url, PHP_URL_PATH) ?: '');
        $absolute = realpath(public_path(ltrim($path, '/')));
        $allowed = array_filter([realpath(public_path('userfiles')), realpath(public_path('media')), realpath(storage_path('app/public'))]);
        if (! $absolute || ! is_file($absolute) || ! collect($allowed)->contains(fn ($root) => str_starts_with(strtolower($absolute), strtolower($root).DIRECTORY_SEPARATOR))) {
            throw ValidationException::withMessages(['fileDinhKem' => __('admin.message.file_not_in_library')]);
        }
        if (in_array(strtolower(pathinfo($absolute, PATHINFO_EXTENSION)), ['php', 'phtml', 'phar', 'html', 'htm', 'js', 'mjs', 'exe', 'bat', 'cmd', 'ps1', 'sh'], true)) {
            throw ValidationException::withMessages(['fileDinhKem' => __('admin.message.file_type_not_allowed')]);
        }
        if ($avatar && ! str_starts_with((string) mime_content_type($absolute), 'image/')) {
            throw ValidationException::withMessages(['avatar' => __('admin.message.anh_dai_dien_phai_la_tap_tin_hinh_anh')]);
        }
        $previous = $post->getMedia($collection);
        $post->addMedia($absolute)->preservingOriginal()->toMediaCollection($collection, 'cms_media');
        if ($avatar) {
            $this->removeMedia($previous);
        }
    }

    private function removeMedia($media): void
    {
        foreach ($media as $item) {
            if ($item->disk === 'cms_media') {
                $item->delete();
            } else {
                $item->newQuery()->whereKey($item->getKey())->delete();
            } // Copied metadata only: keep original Soc Trang files intact.
        }
    }
}
