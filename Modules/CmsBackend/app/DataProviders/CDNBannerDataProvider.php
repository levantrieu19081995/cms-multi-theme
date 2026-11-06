<?php

namespace Modules\CmsBackend\DataProviders;

use Illuminate\Support\Facades\Schema;
use Modules\CmsBackend\Models\PostsBanners;

class CDNBannerDataProvider extends BaseDataProvider
{
    public function __construct(PostsBanners $model)
    {
        // Kế thừa BaseDataProvider
        parent::__construct($model);
    }

    // Các phương thức xử lý dữ liệu khác...
    public function savePostBanner($postId, $ref_id, $redirect, $description, $btnText, bool $showText = true)
    {
        $values = [
            'ref_id' => $ref_id,
            'redirect' => $redirect ?? 0,
            'description' => $description ?? '',
            'btn_text'    => $btnText ?? '',
        ];
        // Cột show_text đến từ migration 2026_11_03_000001; chưa chạy migration thì lưu như cũ.
        if (Schema::hasColumn($this->model->getTable(), 'show_text')) {
            $values['show_text'] = $showText;
        }

        return $this->model->updateOrCreate(['post_id' => $postId], $values);
    }

    public function deletePostBanner($postId)
    {
        return $this->model->where('id', $postId)->delete();
    }
}
