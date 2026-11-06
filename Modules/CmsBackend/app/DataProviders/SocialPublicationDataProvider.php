<?php

declare(strict_types=1);

namespace Modules\CmsBackend\DataProviders;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Post;
use App\Models\Taxonomy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\CmsBackend\Enums\Social\SocialAccountStatus;
use Modules\CmsBackend\Enums\Social\SocialChannel;
use Modules\CmsBackend\Models\Social\SocialAccount;
use Modules\CmsBackend\Models\Social\SocialPublication;
use Modules\CmsBackend\Support\ProjectContext;

/**
 * Every query of the multi-channel feature. Each one is filtered by ProjectContext::key() here, in the query itself, so a
 * database shared by several projects never mixes their posts, publications or accounts, and no caller can forget the filter.
 */
class SocialPublicationDataProvider
{
    private static ?bool $installed = null;

    /** The tables exist (migrations were run). Pages show a notice instead of a SQL error when they do not. */
    public function installed(): bool
    {
        return self::$installed ??= Schema::hasTable('social_publications')
            && Schema::hasTable('social_publication_channels')
            && Schema::hasTable('social_accounts')
            && Schema::hasTable('post_projects');
    }

    private function project(): string
    {
        return ProjectContext::key();
    }

    // ---------------------------------------------------------------- CMS posts the administrator can pick

    /**
     * Published, visible, public posts that belong to the current project (post_projects). Posts of internal categories
     * need a login, so they have no shareable public URL. A post with no project row is never offered.
     */
    private function eligiblePosts(): Builder
    {
        $now = now();

        return Post::query()->withoutGlobalScopes()
            ->where('posts.post_type', PostType::POST->value)
            ->whereNull('posts.deleted_at')
            ->where('posts.status', PostStatus::PUBLISHED->value)
            ->whereNotNull('posts.published_at')
            ->where('posts.published_at', '<=', $now)
            ->where(fn ($query) => $query->whereNull('posts.publication_date_expires')->orWhere('posts.publication_date_expires', '>=', $now))
            ->whereNotNull('posts.slug')->where('posts.slug', '!=', '')
            ->whereIn('posts.id', DB::table('post_projects')->select('post_id')->where('project_key', $this->project()))
            ->whereDoesntHave('taxonomies', fn ($query) => $query->where('loai_chuyen_muc', 'noi_bo'));
    }

    public function findEligiblePost(int $id): ?Post
    {
        return $this->eligiblePosts()->with(['media', 'translations'])->whereKey($id)->first();
    }

    /** Project that owns a post, straight from post_projects (null: owned by none). */
    public function postProjectKey(int $postId): ?string
    {
        $key = DB::table('post_projects')->where('post_id', $postId)->value('project_key');

        return $key === null ? null : (string) $key;
    }

    public function paginateEligiblePosts(string $search, ?int $categoryId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->eligiblePosts()
            ->with(['media', 'translations', 'taxonomies.term'])
            ->when($search !== '', fn (Builder $query) => $query->where(
                'posts.title', 'like', '%'.addcslashes($search, '\\%_').'%'
            ))
            ->when($categoryId, fn (Builder $query) => $query->whereHas(
                'taxonomies', fn ($taxonomies) => $taxonomies->where('term_taxonomy.term_taxonomy_id', $categoryId)
            ))
            ->orderByDesc('posts.published_at')->orderByDesc('posts.id')
            ->paginate($perPage)->withQueryString();
    }

    /** Public categories for the filter (internal ones are not shareable). */
    public function categories(): Collection
    {
        return Taxonomy::query()->where('taxonomy', 'category')
            ->where(fn ($query) => $query->whereNull('loai_chuyen_muc')->orWhere('loai_chuyen_muc', '!=', 'noi_bo'))
            ->with('term')->get()
            ->filter(fn ($taxonomy) => $taxonomy->term && filled($taxonomy->term->name))
            ->sortBy(fn ($taxonomy) => mb_strtolower($taxonomy->term->name))
            ->values();
    }

    // ---------------------------------------------------------------- publications

    private function publications(): Builder
    {
        return SocialPublication::query()->where('project_key', $this->project());
    }

    public function find(int $id): ?SocialPublication
    {
        return $this->publications()->with(['channels.account', 'creator', 'updater', 'post'])->whereKey($id)->first();
    }

    public function findByIdempotencyKey(string $key): ?SocialPublication
    {
        return $this->publications()->where('idempotency_key', $key)->first();
    }

    /** @param array{search?: string, status?: string, channel?: string} $filters */
    public function paginatePublications(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? '');
        $channel = (string) ($filters['channel'] ?? '');

        return $this->publications()
            ->with(['channels', 'creator', 'post'])
            ->when($search !== '', fn (Builder $query) => $query->where(
                'title', 'like', '%'.addcslashes($search, '\\%_').'%'
            ))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status),
                // Archived items are kept but hidden unless asked for.
                fn (Builder $query) => $query->where('status', '!=', 'archived'))
            ->when($channel !== '', fn (Builder $query) => $query->whereHas(
                'channels', fn ($channels) => $channels->where('channel', $channel)
            ))
            ->latest('id')
            ->paginate($perPage)->withQueryString();
    }

    // ---------------------------------------------------------------- accounts

    private function accounts(): Builder
    {
        return SocialAccount::query()->where('project_key', $this->project());
    }

    /** The connected account of a platform for the current project, if any. */
    public function connectedAccount(SocialChannel $channel): ?SocialAccount
    {
        return $this->accounts()->where('platform', $channel->value)
            ->where('status', SocialAccountStatus::CONNECTED->value)->latest('id')->get()
            ->first(fn (SocialAccount $account) => $account->isConnected());
    }

    public function connectedAccountCount(): int
    {
        return $this->accounts()->where('status', SocialAccountStatus::CONNECTED->value)->count();
    }

    // ---------------------------------------------------------------- counters (real rows only)

    /** @return array<string, int> status value => number of publications */
    public function countByStatus(): array
    {
        return $this->publications()->select('status', DB::raw('COUNT(*) AS total'))
            ->groupBy('status')->pluck('total', 'status')->map(fn ($total) => (int) $total)->all();
    }

    /** @return array<string, array<string, int>> channel => status => number of deliveries */
    public function countByChannelAndStatus(): array
    {
        $rows = DB::table('social_publication_channels as c')
            ->join('social_publications as p', 'p.id', '=', 'c.social_publication_id')
            ->where('p.project_key', $this->project())
            ->select('c.channel', 'c.status', DB::raw('COUNT(*) AS total'))
            ->groupBy('c.channel', 'c.status')->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->channel][$row->status] = (int) $row->total;
        }

        return $result;
    }

    /** @return Collection<int, object{creator: string, total: int}> */
    public function countByCreator(): Collection
    {
        return DB::table('social_publications as p')
            ->leftJoin('users as u', 'u.id', '=', 'p.created_by')
            ->where('p.project_key', $this->project())
            ->select('p.created_by', 'u.name', DB::raw('COUNT(*) AS total'))
            ->groupBy('p.created_by', 'u.name')->orderByDesc('total')->get();
    }
}
