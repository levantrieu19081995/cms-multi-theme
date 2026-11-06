'use strict';
(function () {
    console.log('Banner index.js loaded');

    function reloadTable() {
        const form = $('#searchForm');
        // Lấy URL từ data-url nếu có, không thì fallback action
        const url = form.data('url') || form.attr('action');
        console.log('url', url);
        const formData = form.serialize(); // Lấy dữ liệu từ form

        $.ajax({
            url: url, // Sử dụng action của form hoặc route index
            type: 'GET',
            data: formData,
            success: function (response) {
                $('#table-trang-container').html(response); // Thay thế nội dung bảng
                _init(); // Khởi tạo lại các sự kiện
            },
            error: function (xhr) {
                console.error('Lỗi chi tiết:', xhr);
                // toastr.error(xhr.responseJSON?.message || window.cmsAdminTranslations.co_loi_xay_ra_vui_long_thu_lai);
                // reloadTable();
            }
        });
        // Notiflix.Loading.remove();
    }

    const _init = () => {
        $('.switch-input').on('change', function () {
            const postId = $(this).data('post-id'); // Lấy ID bài viết

            // Gửi yêu cầu AJAX để cập nhật
            $.ajax({
                url: (window.cmsUrls && window.cmsUrls.updateHomepage) || '/cms/api/update-homepage', // URL API
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
                    reloadTable();
                },
                error: function (xhr) {
                    // Hiển thị thông báo lỗi
                    toastr.error(xhr.responseJSON?.message ||
                        window.cmsAdminTranslations.co_loi_xay_ra_vui_long_thu_lai);
                    reloadTable();
                }
            });
        });
    }


    _init();
})();