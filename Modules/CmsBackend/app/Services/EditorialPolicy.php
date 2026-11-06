<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\Taxonomy;
use App\Models\User;
use Modules\CmsBackend\Models\UserTermPermission;
use Modules\CmsBackend\Support\EditorialPermissions as Permissions;

final class EditorialPolicy
{
    public function has(User $user, string $type, string $action): bool
    {
        return $user->can(Permissions::label($type, $action));
    }

    public function editor(User $user, string $type): bool
    {
        return $this->has($user, $type, 'approve') || $this->has($user, $type, 'publish');
    }

    public function categoryIds(User $user): array
    {
        $cacheKey = '_cms_editorial_categories_'.$user->getAuthIdentifier();
        if (request()->attributes->has($cacheKey)) {
            return request()->attributes->get($cacheKey);
        }
        $query = Taxonomy::where('taxonomy', 'category')->whereHas('term');
        if (! $user->hasRole('Quản trị')) {
            $query->whereIn('term_id', UserTermPermission::where('user_id', $user->id)->where('can_create', 1)->pluck('term_id'));
        }

        $ids = $query->pluck('term_taxonomy_id')->map(fn ($id) => (int) $id)->all();
        request()->attributes->set($cacheKey, $ids);

        return $ids;
    }

    public function view(User $user, Post $post): bool
    {
        if (! $this->has($user, $post->post_type, 'view')) {
            return false;
        }
        $categories = $post->taxonomies->where('taxonomy', 'category')->pluck('term_taxonomy_id')->map(fn ($id) => (int) $id)->all();
        $allowed = $this->categoryIds($user);
        $inScope = $categories === [] ? $post->author_id == $user->id || $user->hasRole('Quản trị') : array_diff($categories, $allowed) === [];

        return $inScope && ($this->editor($user, $post->post_type) || $post->author_id == $user->id);
    }

    public function allows(User $user, Post $post, string $action): bool
    {
        if (! $this->view($user, $post)) {
            return false;
        }
        $state = $post->status;
        $own = $post->author_id == $user->id;
        $authorEditable = $own && in_array($state, [PostStatus::DRAFT->value, PostStatus::NOT_APPROVED->value], true);
        // Tài khoản được Developer bật "sửa trực tiếp bài đã đăng" (Developer Settings → Tài khoản): lưu chỉnh sửa khi bài đang đăng, trạng thái giữ nguyên.
        $editsPublished = $state === PostStatus::PUBLISHED->value && $this->mayEditPublished($user);

        return match ($action) {
            'save_draft' => $authorEditable && $this->has($user, $post->post_type, 'create') && $this->has($user, $post->post_type, 'update'),
            'submit_for_approval' => ($authorEditable || ($this->editor($user, $post->post_type) && $state === PostStatus::STOP_PUBLISHING->value)) && $this->has($user, $post->post_type, 'create') && $this->has($user, $post->post_type, 'update'),
            'save_changes' => $this->has($user, $post->post_type, 'update') && ($authorEditable || $editsPublished || ($this->editor($user, $post->post_type) && in_array($state, [PostStatus::PENDING_APPROVAL->value, PostStatus::STOP_PUBLISHING->value], true))),
            'approved' => $state === PostStatus::PENDING_APPROVAL->value && $this->has($user, $post->post_type, 'approve'),
            'reject' => in_array($state, [PostStatus::PENDING_APPROVAL->value, PostStatus::APPROVED->value, PostStatus::STOP_PUBLISHING->value], true) && $this->has($user, $post->post_type, 'approve'),
            // Bật/tắt hiển thị trang chủ không đổi trạng thái đăng nên không cần ngưng xuất bản; điều kiện trạng thái kiểm ở EditorialPostController::showHome.
            'show_home' => $this->has($user, $post->post_type, 'update'),
            'publish' => $state === PostStatus::APPROVED->value && $this->has($user, $post->post_type, 'publish'),
            'stop_publishing' => $state === PostStatus::PUBLISHED->value && $this->has($user, $post->post_type, 'publish'),
            'force_delete' => $user->hasRole('Quản trị') && $this->has($user, $post->post_type, 'delete') && ($post->deleted_at !== null || in_array($state, [PostStatus::DRAFT->value, PostStatus::NOT_APPROVED->value, PostStatus::STOP_PUBLISHING->value], true)),
            'delete' => in_array($state, [PostStatus::DRAFT->value, PostStatus::NOT_APPROVED->value, PostStatus::STOP_PUBLISHING->value], true) && $this->has($user, $post->post_type, 'delete'),
            default => false,
        };
    }

    /** Cờ users.can_edit_published; cột chưa có (chưa migrate) thì không ai có cờ. */
    public function mayEditPublished(User $user): bool
    {
        return (bool) ($user->can_edit_published ?? false);
    }

    public static function stateLabel(string $state): string
    {
        return $state === PostStatus::NOT_APPROVED->value ? __('admin.status.changes_requested') : (PostStatus::tryFrom($state)?->label() ?? $state);
    }
}
