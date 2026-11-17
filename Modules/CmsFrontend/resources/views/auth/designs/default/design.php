<?php

// Mô tả của bộ giao diện đăng nhập (xem docs/login-designs/HUONG-DAN-THEM-GIAO-DIEN.md). Chỉ trả về mảng, không chạy logic.
return [
    'label' => 'Thẻ giữa màn hình (ảnh nền toàn trang)',
    'description' => 'Một thẻ trắng ở giữa trên ảnh nền toàn màn hình. Chuyển Login/Dashboard kiểu trượt ngang.',
    // Hiệu ứng chuyển Login <-> Dashboard (effects/auth): 'push-horizontal' = hai trang trượt cạnh nhau; null = cửa trượt đôi.
    'transition' => 'push-horizontal',
];
