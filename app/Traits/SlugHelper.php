<?php

namespace App\Traits;

use App\Models\Post;

trait SlugHelper
{
    /**
     * Kiểm tra và trả về slug duy nhất
     *
     * @param string $slug
     * @param int|null $postId
     * @return string
     */
    protected function checkUniqueSlug($slug, $postId = null)
    {
        // Khởi tạo slug ban đầu để sử dụng sau này
        $originalSlug = $slug;

        // Đếm số lần lặp để tạo slug duy nhất
        $i = 1;

        // Kiểm tra xem slug đã tồn tại trong cơ sở dữ liệu hay chưa
        while (Post::where('slug', $slug)
            ->when($postId, function ($query, $postId) {
                // Bỏ qua bài viết hiện tại nếu đang cập nhật
                return $query->where('id', '!=', $postId);
            })
            ->exists()
        ) {
            // Nếu slug đã tồn tại, thêm số phía sau slug
            $slug = $originalSlug . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
