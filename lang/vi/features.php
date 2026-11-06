<?php

// Tên/mô tả chức năng hệ thống hiển thị trong Feature Registry và bộ chọn menu (definition chỉ giữ khóa, không viết cứng chữ).
return [
    'survey' => [
        'name' => 'Khảo sát',
        'description' => 'Tạo khảo sát nhiều phần, thu thập phản hồi công khai, thống kê và xuất Excel.',
        'targets' => [
            'index' => 'Danh sách khảo sát',
        ],
    ],
    'contact' => [
        'name' => 'Liên hệ – Góp ý',
        'description' => 'Nhận liên hệ, góp ý từ biểu mẫu công khai và quản lý trong quản trị.',
        'targets' => [
            'index' => 'Trang liên hệ',
        ],
    ],
    'multilingual' => [
        'name' => 'Đa ngôn ngữ (Việt/English)',
        'description' => 'Cho phép nội dung công khai có thêm ngôn ngữ phụ: nút chọn ngôn ngữ, tab và ô nhập bản dịch trong quản trị. Tắt thì chỉ còn tiếng Việt, dữ liệu bản dịch đã nhập vẫn được giữ.',
    ],
];
