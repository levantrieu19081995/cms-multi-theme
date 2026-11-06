'use strict';

(function () {
    // Cho phép bình luận bài viết
    $('.switch-input-comment').on('change', function () {
        const postId = $(this).data('post-id'); // Lấy ID bài viết

        // Gửi yêu cầu AJAX để cập nhật
        $.ajax({
            url: (window.cmsUrls && window.cmsUrls.updateCommentpage) || '/cms/api/update-commentpage', // URL API
            method: 'POST', // Phương thức HTTP
            data: {
                id: postId,
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                    'content') // CSRF token cho Laravel
            },
            success: function (response) {
                // Hiển thị thông báo thành công bằng Toastr.js
                toastr.success(response.message || window.cmsAdminTranslations.cap_nhat_thanh_cong);
                // reloadTable();
            },
            error: function (xhr) {
                // Hiển thị thông báo lỗi
                toastr.error(xhr.responseJSON?.message ||
                    window.cmsAdminTranslations.co_loi_xay_ra_vui_long_thu_lai);
                // reloadTable();
            }
        });
    });
})();