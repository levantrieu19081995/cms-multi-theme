<?php

namespace Modules\CmsFrontend\Services;


use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\CmsFrontend\DataProviders\TinTucDataProvider;

class TinTucService
{
    protected TinTucDataProvider $provider;

    public function __construct(TinTucDataProvider $provider)
    {
        $this->provider = $provider;
    }

    public function getList(
        string $postTypes,
        ?string $categorySlug = null,
        ?string $filter = null,
        $except_id = null,
        array $orderby = ['published_at:desc']
    ) {
        return $this->provider
            ->getQueryList($postTypes, $categorySlug, $filter, $except_id, $orderby);
    }


    public function paginateTinTuc(
        string $postTypes,
        ?string $categorySlug = null,
        ?string $filter = null,
        int $pageSize = 20,
        $page = 1,
        $except_id = null,
        array $orderby = ['published_at:desc'],
        // ?int $show_home_page = 1
        ?int $show_home_page = null
    ): LengthAwarePaginator {
        return $this->provider->getQueryList($postTypes, $categorySlug, $filter, $except_id, $orderby, $show_home_page)->paginate($pageSize, ['*'], 'page', $page)->withQueryString();
    }

    public function paginateTinTucTrangChu(
        string $postTypes,
        ?string $categorySlug = null,
        ?string $filter = null,
        int $pageSize = 20,
        $page = 1,
        $except_id = null,
        array $orderby = ['published_at:desc'],
        ?int $show_home_page = 1
        // ?int $show_home_page = null
    ): LengthAwarePaginator {
        return $this->provider->getQueryList($postTypes, $categorySlug, $filter, $except_id, $orderby, $show_home_page)->paginate($pageSize, ['*'], 'page', $page);
    }

    public function getTinTucPublic(?int $numItems = 10)
    {
        return $this->provider->getTinTucPublic($numItems);
    }

    public function getChiTietTinTucBySlug(string $slug)
    {
        return $this->provider->getPostBySlug($slug);
    }

    public function getDanhSachBinhLuan(string $post_id)
    {
        return $this->provider->getCommentsByPost($post_id);
    }
}
