<?php

namespace Modules\CmsBackend\Services;

use App\DataProviders\PostDataProvider;
use App\Enums\PostStatus;
use App\Enums\PostType;
use Modules\CmsBackend\DataProviders\CDNBannerDataProvider;

class CDNBannerService extends BaseService
{
    protected CDNBannerDataProvider $cdnBannerDataProvider;

    protected PostDataProvider $postDataProvider;

    public function __construct(PostDataProvider $postDataProvider, CDNBannerDataProvider $cdnBannerDataProvider)
    {
        parent::__construct($postDataProvider, $cdnBannerDataProvider);
        $this->cdnBannerDataProvider = $cdnBannerDataProvider;
        $this->postDataProvider = $postDataProvider;
    }

    /**
     * Lấy danh sách banner
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getDanhSachBanner(array $params = [])
    {
        $postTypes = [PostType::BANNER->value];
        $postStatuses = [PostStatus::PUBLISHED->value];
        $authorId = $params['author_id'] ?? null;
        $categoryIds = $params['category_ids'] ?? [];
        $tagIds = $params['tag_ids'] ?? [];
        $filter = $params['filter'] ?? '';
        $sortBy = $params['sort_by'] ?? 'created_at';
        $order = $params['order'] ?? 'desc';
        $pageSize = $params['page_size'] ?? 5;
        $page = $params['page'] ?? 1;
        $showHomePage = $params['show_home_page'] ?? 1;

        return $this->postDataProvider->paginate(
            implode(',', $postTypes),
            $postStatuses,
            $authorId,
            $categoryIds,
            $tagIds,
            $filter,
            $sortBy,
            $order,
            $pageSize,
            $page,
            $showHomePage
        );
    }

    public function getBannerById($id)
    {
        return $this->cdnBannerDataProvider->find($id, 'post_id', []);
    }

    public function savePostBanner($postId, $ref_id, $redirect, $description, $btnText, bool $showText = true)
    {
        return $this->cdnBannerDataProvider->savePostBanner($postId, $ref_id, $redirect, $description, $btnText, $showText);
    }

    public function deletePostBanner($id)
    {
        return $this->cdnBannerDataProvider->deletePostBanner($id);
    }

    /**
     * Thêm các function khác (nếu có)
     */
}
