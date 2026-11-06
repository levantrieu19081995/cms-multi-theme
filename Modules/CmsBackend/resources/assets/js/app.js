'use strict';
(function () {
    // HÀM DÙNG CHUNG CHO CHỨC NĂNG TÌM KIẾM
    const $form = $('[data-search-form]');
    const $container = $('[data-table-container]');
    console.log('container', $container);

    if ($form.length && $container.length) {
        $form.on('submit', function (e) {
            e.preventDefault();
            $.ajax({
                url: $form.attr('action'),
                type: 'GET',
                data: $form.serialize(),
                success: function (response) {
                    $container.innerHtml = response;
                    // Optionally re-init events here
                },
                error: function () {
                    toastr.error(window.cmsAdminTranslations.co_loi_xay_ra_khong_the_tim_kiem);
                }
            });
        });
    }

    // HÀM DÙNG CHO CHỨC NĂNG CHỌN HIỂN THỊ TRANG CHỦ
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
            },
            error: function (xhr) {
                // Hiển thị thông báo lỗi
                toastr.error(xhr.responseJSON?.message ||
                    window.cmsAdminTranslations.co_loi_xay_ra_vui_long_thu_lai);

            }
        });
    });

    document.addEventListener('DOMContentLoaded', function () {

        let toggleButton = document.getElementById('toggleFilter');
        let filterSection = document.getElementById('advanced-filter');

        if (!toggleButton || !filterSection) return;

        // Update button text based on the initial state
        toggleButton.textContent = filterSection.classList.contains('show') ? window.cmsAdminTranslations.basic : window.cmsAdminTranslations.advanced;

        // Listen for the collapse shown event
        filterSection.addEventListener('shown.bs.collapse', function () {
            toggleButton.textContent = window.cmsAdminTranslations.basic;
        });

        // Listen for the collapse hidden event
        filterSection.addEventListener('hidden.bs.collapse', function () {
            toggleButton.textContent = window.cmsAdminTranslations.advanced;
        });
    });
})();