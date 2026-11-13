<?php

// Homepage Layout Manager — Quản lý bố cục trang chủ (Developer Settings + Cài đặt trang chủ).
return [
    'blocks' => [
        'hero' => ['name' => 'Banner đầu trang', 'description' => 'Cụm đầu trang (banner/hero). Nội dung do màn hình Banner quản lý.'],
        'notice-ticker' => ['name' => 'Dải thông báo mới', 'description' => 'Dòng thông báo mới nhất dưới banner, lấy từ chuyên mục thông báo của trang chủ.'],
        'article-list' => ['name' => 'Tin tức nổi bật', 'description' => 'Bài viết được bật "Hiển thị trang chủ" trong danh sách tin.'],
        'information' => ['name' => 'Thông báo', 'description' => 'Bài mới của chuyên mục thông báo.'],
        'documents' => ['name' => 'Văn bản, tài liệu', 'description' => 'Văn bản, tài liệu như một cụm lớn riêng, lọc theo loại hoặc nhóm văn bản.'],
        'schedules-trace' => ['name' => 'Lịch làm việc', 'description' => 'Hai chuyên mục lịch làm việc (lãnh đạo Sở, Ban giám đốc).'],
        'trace' => ['name' => 'Truy xuất nguồn gốc', 'description' => 'Hai cổng truy xuất nguồn gốc: tiêu đề, chú thích, đường dẫn và ảnh.'],
        'activities' => ['name' => 'Chuyên mục nổi bật', 'description' => 'Mỗi chuyên mục chọn ở Cài đặt trang chủ là một hàng bài.'],
        'events' => ['name' => 'Sự kiện nổi bật', 'description' => 'Sự kiện bật hiển thị trang chủ.'],
        'examples' => ['name' => 'Gương tiêu biểu', 'description' => 'Gương tiêu biểu được đăng (theme Khuyến nông đặt tiêu đề "Khuyến nông số").'],
        'services' => ['name' => 'Dịch vụ', 'description' => 'Dịch vụ đang hoạt động.'],
        'media' => ['name' => 'Thư viện ảnh và video', 'description' => 'Album ảnh và video được bật hiển thị trang chủ.'],
        'gallery' => ['name' => 'Thư viện hình ảnh', 'description' => 'Album ảnh bật hiển thị trang chủ.'],
        'videos' => ['name' => 'Video', 'description' => 'Video bật hiển thị trang chủ.'],
        'partners-support' => ['name' => 'Đối tác và liên kết', 'description' => 'Đối tác/liên kết hiển thị trang chủ.'],
        'partners' => ['name' => 'Đơn vị phối hợp', 'description' => 'Đối tác/liên kết hiển thị trang chủ.'],
        'support' => ['name' => 'Hỗ trợ và tư vấn', 'description' => 'Gửi câu hỏi (Hỏi đáp) và liên hệ tư vấn (khi chức năng Liên hệ bật).'],
        'statistics' => ['name' => 'Con số nổi bật', 'description' => 'Bốn con số nổi bật nhập ở Cài đặt trang chủ.'],
        'shortcuts' => ['name' => 'Lối tắt đầu trang', 'description' => 'Lối tắt lấy từ menu chính của website.'],
        'section' => ['name' => 'Cụm theo chuyên mục', 'description' => 'Bài mới nhất của một chuyên mục (giới thiệu, chương trình, nghiên cứu, tuyển sinh, đề án...).'],
        'audience-links' => ['name' => 'Liên kết theo đối tượng', 'description' => 'Liên kết dành cho từng nhóm người dùng, khai báo trong cấu hình theme.'],
        'quick-links' => ['name' => 'Lối tắt chức năng', 'description' => 'Dải lối tắt tới Văn bản, Thư viện ảnh, Dịch vụ, Hỏi đáp.'],
        'cta' => ['name' => 'Kêu gọi liên hệ', 'description' => 'Khối kêu gọi dẫn tới trang Liên hệ (chỉ hiện khi chức năng Liên hệ đang bật).'],
        'contact' => ['name' => 'Cụm liên hệ', 'description' => 'Cụm liên hệ cuối trang (chỉ hiện khi chức năng Liên hệ đang bật).'],
    ],

    'zones' => ['hero' => 'Đầu trang', 'before_main' => 'Trước nội dung chính', 'main' => 'Nội dung chính', 'after_main' => 'Sau nội dung chính'],

    'fields' => [
        'notice_ticker_help' => 'Chuyên mục thông báo chọn ở cụm "Thông báo"', 'schedule_department_title' => 'Tiêu đề lịch làm việc lãnh đạo Sở', 'schedule_board_title' => 'Tiêu đề lịch làm việc Ban giám đốc', 'trace_market_title' => 'Tiêu đề sàn giao dịch truy xuất', 'trace_market_caption' => 'Mô tả ngắn sàn giao dịch truy xuất', 'trace_portal_title' => 'Tiêu đề cổng truy xuất nguồn gốc', 'trace_portal_caption' => 'Mô tả ngắn cổng truy xuất nguồn gốc', 'banner_link' => 'Quản lý banner', 'notice_category' => 'Chuyên mục thông báo', 'news_link' => 'Quản lý tin tức (bật "Hiển thị trang chủ" trong danh sách)',
        'document_filter' => 'Văn bản hiển thị', 'documents_link' => 'Quản lý văn bản', 'schedule_department' => 'Chuyên mục lịch làm việc lãnh đạo Sở',
        'schedule_board' => 'Chuyên mục lịch làm việc Ban giám đốc', 'trace_market_url' => 'Liên kết sàn giao dịch truy xuất', 'trace_market_image' => 'Ảnh sàn giao dịch truy xuất',
        'trace_portal_url' => 'Liên kết cổng truy xuất nguồn gốc', 'trace_portal_image' => 'Ảnh cổng truy xuất nguồn gốc', 'featured_categories' => 'Chuyên mục nổi bật (theo thứ tự tab)',
        'events_link' => 'Quản lý sự kiện', 'examples_link' => 'Quản lý gương tiêu biểu', 'gallery_link' => 'Quản lý thư viện ảnh', 'video_link' => 'Quản lý video',
        'partners_link' => 'Quản lý đối tác', 'services_link' => 'Quản lý dịch vụ', 'support_link' => 'Quản lý hỏi đáp', 'statistics_link' => 'Nhập các con số nổi bật', 'menu_link' => 'Quản lý menu',
        'section_category' => 'Chuyên mục', 'section_category_help' => 'Để trống để dùng chuyên mục mặc định của theme.',
    ],

    'settings' => [
        'title' => 'Nội dung các cụm trang chủ', 'intro' => 'Chỉ hiện các cụm đang được bật cho giao diện hiện tại. Bật/tắt và sắp xếp cụm do Developer quản lý.',
        'none' => 'Chưa có cụm nào được bật.', 'manage' => 'Đi tới chức năng quản lý', 'managed_by' => 'Nội dung do chức năng riêng quản lý.',
        'missing' => 'Chuyên mục đã bị xóa (ID :ids). Hãy chọn lại.', 'choose' => '-- Chọn --', 'no_settings' => 'Cụm này không có thiết lập nội dung.',
        'document_latest' => 'Mới nhất (tất cả loại)', 'document_type' => 'Loại', 'document_group' => 'Nhóm', 'saved' => 'Đã lưu cấu hình các cụm trang chủ.',
        'ordered_hint' => 'Thứ tự chọn là thứ tự hiển thị.',
    ],

    'variants' => ['standard' => 'Chuẩn', 'featured' => 'Nổi bật'],

    'effects' => ['reveal' => 'Chữ hiện theo dòng', 'stagger' => 'Trồi lên lần lượt', 'count-up' => 'Đếm số', 'crossfade-slider' => 'Slider mờ dần', 'gallery-orbit' => 'Vòng ảnh 3D'],

    'ui' => [
        'apply_now' => 'Áp dụng ngay', 'apply_now_confirm' => 'Lưu và áp dụng bố cục này lên trang chủ công khai ngay, không qua xem trước?', 'applied_now' => 'Đã áp dụng bố cục lên trang chủ (:count cụm).',
        'effect' => 'Hiệu ứng', 'effect_default' => 'Mặc định của theme', 'effect_none' => 'Không hiệu ứng', 'effect_reveal' => 'Chữ hiện theo dòng', 'effect_stagger' => 'Trồi lên lần lượt', 'effect_not_ready' => 'Chưa có cột hiệu ứng (chưa chạy migration add_effect_key_to_homepage_theme_blocks): các cụm đang dùng hiệu ứng mặc định của theme và chưa chọn được hiệu ứng.', 'tab' => 'Bố cục trang chủ', 'theme' => 'Giao diện', 'layout_state' => 'Bố cục', 'state_live' => 'Đã cài trong database', 'state_default' => 'Chưa cài (đang hiện bố cục mặc định)',
        'note' => 'Bố cục bên dưới là bản đang chỉnh (chưa lưu). "Xem trước" tạo bản tạm trên máy chủ để xem thử, không đổi trang chủ. "Áp dụng ngay" lưu và đổi trang chủ công khai ngay lập tức.',
        'not_ready' => 'Chưa có bảng dữ liệu của chức năng (chưa chạy migration). Trang chủ đang dùng bố cục mặc định trong code và chưa lưu được thay đổi.',
        'registry_errors' => 'Khai báo trong code chưa nhất quán:', 'no_theme' => 'Không có giao diện nào hỗ trợ bố cục trong bản build này.',
        'install' => 'Cài bố cục mặc định', 'installed' => 'Đã cài :count cụm.', 'reset' => 'Khôi phục mặc định', 'confirm_reset' => 'Khôi phục bố cục mặc định của giao diện này? Vị trí, variant và bật/tắt sẽ được đặt lại; cấu hình nội dung giữ nguyên.',
        'reset_done' => 'Đã khôi phục bố cục mặc định.', 'drag' => 'Kéo để sắp xếp', 'enabled' => 'Bật',
        'variant' => 'Kiểu hiển thị', 'zone' => 'Vùng', 'move_up' => 'Lên', 'move_down' => 'Xuống', 'missing_definition' => 'Thiếu khai báo cụm', 'missing_renderer' => 'Thiếu giao diện hiển thị',
        'preview' => 'Xem trước', 'preview_hint' => 'Kiểm tra bằng chính giao diện, dữ liệu và hiệu ứng của trang thật trước khi áp dụng.',
        'review_title' => 'Xem trước bố cục trang chủ', 'preview_mode' => 'CHẾ ĐỘ XEM TRƯỚC', 'snapshot_at' => 'Bản xem trước tạo lúc', 'not_applied' => 'Chưa áp dụng', 'same_as_live' => 'Giống bố cục đang dùng',
        'device' => 'Kích thước', 'desktop' => 'Desktop', 'tablet' => 'Tablet', 'mobile' => 'Mobile', 'replay' => 'Chạy lại hiệu ứng', 'back_to_config' => 'Quay lại cấu hình',
        'apply' => 'Áp dụng cấu hình', 'confirm_apply' => 'Áp dụng bố cục này làm bố cục Live của giao diện?', 'cannot_apply' => 'Không thể áp dụng:', 'applied' => 'Đã áp dụng bố cục (:count cụm).',
    ],

    'errors' => [
        'preview_invalid' => 'Cấu hình không hợp lệ (vùng, kiểu hiển thị hoặc cụm không được giao diện hỗ trợ).', 'preview_expired' => 'Bản xem trước đã hết hạn hoặc không thuộc phiên này. Hãy tạo lại.',
        'apply_blocked' => 'Còn cụm thiếu giao diện hiển thị, tệp hiệu ứng hoặc cấu hình sai nên chưa thể áp dụng.', 'not_ready' => 'Chưa chạy migration của chức năng này.',
        'theme_unknown' => 'Giao diện không được hỗ trợ.', 'unknown_instance' => 'Cụm không tồn tại.', 'unexpected' => 'Có lỗi không mong muốn, xem log.',
        'theme_not_in_build' => 'Giao diện này không có tệp giao diện (asset) trong bản build hiện tại nên không thể xem trước.',
        'required' => 'Bắt buộc nhập.', 'category_invalid' => 'Chuyên mục không tồn tại.', 'number_invalid' => 'Số không hợp lệ hoặc ngoài khoảng cho phép.',
        'too_long' => 'Nội dung quá dài.', 'option_invalid' => 'Lựa chọn không hợp lệ.', 'url_invalid' => 'Đường dẫn không hợp lệ (http/https hoặc bắt đầu bằng /).',
    ],

    'issues' => [
        'missing_definition' => 'Thiếu khai báo cụm', 'invalid_zone' => 'Vùng không hợp lệ', 'invalid_effect' => 'Hiệu ứng không hợp lệ', 'missing_effect' => 'Thiếu hiệu ứng hoặc tệp hiệu ứng', 'invalid_variant' => 'Kiểu hiển thị không hợp lệ', 'missing_renderer' => 'Thiếu giao diện hiển thị',
        'unknown_instance' => 'Cụm không được giao diện hỗ trợ', 'duplicate' => 'Cụm bị trùng', 'theme_unknown' => 'Giao diện không hợp lệ', 'payload' => 'Dữ liệu gửi lên không hợp lệ',
    ],

    'render' => [
        'block_error' => 'Không hiển thị được cụm này.', 'missing_settings' => 'Cụm này chưa chọn nguồn nội dung cần thiết:',
    ],
];
