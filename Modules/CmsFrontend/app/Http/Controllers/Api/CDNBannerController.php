<?php

namespace Modules\CmsFrontend\Http\Controllers\Api;

use Modules\CmsBackend\Http\Controllers\Shared\BannerController;
use Modules\CmsBackend\Services\CDNBannerService;
use Illuminate\Support\Str;

class CDNBannerController extends BannerController
{
    protected CDNBannerService $cdnBannerService;

    public function __construct(CDNBannerService $cdnBannerService)
    {
        $this->cdnBannerService = $cdnBannerService;
        // parent::__construct();
    }

    public function getDsBanner()
    {
        try {
            /** @var \Illuminate\Pagination\LengthAwarePaginator $banners */

            $banners = $this->cdnBannerService->getDanhSachBanner();
            // dd($banners);
            if ($banners->isEmpty()) {
                return response()->json([
                    'code'    => 404,
                    'status'  => 'error',
                    'message' => 'Không tìm thấy danh sách banner.',
                    'data'    => [],
                ], 404);
            }


            // Format từng item
            $formattedItems = $banners->getCollection()->map(function ($banner) {
                // Kiểm tra slug đã có domain chưa
                $slug = $banner->slug;
                if (!Str::startsWith($slug, ['http://', 'https://'])) {
                    $slug = \Modules\CmsBackend\Support\ModuleUrl::url('/' . preg_replace('/\.htm$/', '', ltrim($slug, '/')) . '.htm');
                }
                // Lấy thông tin liên quan từ bảng posts_banners
                $id = $banner->id;
                $bannerData = $this->cdnBannerService->getBannerById($id);

                return [
                    'id'         => $banner->id,
                    'title'      => $banner->title,
                    'image'      => $banner->getFirstMediaUrl('avatars'),
                    'slug'       => $slug,
                    'description' => $bannerData->description ?? '',
                    'btn_text' => $bannerData->btn_text ?? 'Xem thêm'
                ];
            });

            // Gán lại collection mới đã format vào paginator
            $banners->setCollection($formattedItems);

            return response()->json([
                'code'    => 200,
                'status'  => 'success',
                'message' => 'Lấy danh sách banner thành công.',
                'data'    => $banners,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code'    => 500,
                'status'  => 'error',
                'message' => 'Lỗi server',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
