<?php

// Định hướng nghề nghiệp (RIASEC) trên nền Khảo sát. Chữ mặc định của 6 nhóm chỉ dùng khi quản trị chưa chỉnh tên/mô tả trong
// màn "Nhóm & ngành nghề"; ngành gợi ý và câu hỏi do quản trị nhập, không nằm ở đây.
return [
    'groups' => [
        'R' => ['name' => 'Kỹ thuật – Thực hành (Realistic)', 'description' => 'Thích làm việc với máy móc, công cụ, thiết bị, cây trồng, vật nuôi; ưa hoạt động thực hành, tay chân và những kết quả nhìn thấy được.'],
        'I' => ['name' => 'Nghiên cứu – Phân tích (Investigative)', 'description' => 'Thích quan sát, tìm hiểu nguyên nhân, phân tích dữ liệu và giải quyết vấn đề bằng tư duy logic, khoa học.'],
        'A' => ['name' => 'Nghệ thuật – Sáng tạo (Artistic)', 'description' => 'Thích tự do sáng tạo và thể hiện ý tưởng qua hình ảnh, âm nhạc, văn chương, thiết kế; ưa môi trường linh hoạt, ít khuôn mẫu.'],
        'S' => ['name' => 'Xã hội – Hỗ trợ (Social)', 'description' => 'Thích giúp đỡ, giảng dạy, chăm sóc và làm việc cùng mọi người; giỏi lắng nghe, giao tiếp và kết nối.'],
        'E' => ['name' => 'Quản lý – Kinh doanh (Enterprising)', 'description' => 'Thích thuyết phục, dẫn dắt, đặt mục tiêu và đưa ra quyết định; ưa cạnh tranh, kinh doanh và chấp nhận rủi ro.'],
        'C' => ['name' => 'Nghiệp vụ – Tổ chức (Conventional)', 'description' => 'Thích công việc có quy trình rõ ràng, số liệu, hồ sơ, sổ sách; làm việc cẩn thận, ngăn nắp và chính xác.'],
    ],

    // Màn quản trị: /cms/khao-sat/nghe-nghiep
    'admin' => [
        'title' => 'Nhóm & ngành nghề (Định hướng nghề nghiệp)',
        'back' => 'Khảo sát',
        'intro' => 'Chỉnh cách hiển thị 6 nhóm RIASEC và khai báo các ngành gợi ý cho người làm khảo sát định hướng nghề nghiệp. Câu hỏi được dựng trong từng khảo sát (loại "Định hướng nghề nghiệp").',
        'groups_title' => 'Sáu nhóm RIASEC',
        'groups_hint' => 'Để trống cả tên và mô tả để dùng chữ mặc định.',
        'group_name' => 'Tên hiển thị',
        'group_description' => 'Mô tả',
        'groups_save' => 'Lưu nhóm',
        'groups_saved' => 'Đã lưu 6 nhóm RIASEC.',
        'majors_title' => 'Ngành gợi ý',
        'majors_hint' => 'Mỗi ngành gắn mã RIASEC 1–3 chữ cái (chữ đầu quan trọng nhất), ví dụ "IRA". Kết quả của người làm được so với mã này để xếp hạng ngành gợi ý.',
        'majors_empty' => 'Chưa có ngành nào. Thêm ngành để trang kết quả có gợi ý; nếu để trống, người làm sẽ thấy "Thông tin đang được cập nhật".',
        'major_add' => 'Thêm ngành',
        'major_edit' => 'Sửa ngành',
        'major_name' => 'Tên ngành',
        'major_code' => 'Mã RIASEC',
        'major_code_hint' => 'Ví dụ: I, IR, IRA',
        'major_description' => 'Mô tả ngắn',
        'major_url' => 'Liên kết tìm hiểu thêm',
        'major_order' => 'Thứ tự',
        'major_active' => 'Hiển thị',
        'major_inactive' => 'Ẩn',
        'major_save' => 'Lưu ngành',
        'major_saved' => 'Đã lưu ngành.',
        'major_deleted' => 'Đã xóa ngành.',
        'major_confirm_delete' => 'Xóa ngành này?',
        'cancel' => 'Hủy',
        'edit' => 'Sửa',
        'delete' => 'Xóa',
        'actions' => 'Thao tác',
        'menu_link' => 'Nhóm & ngành nghề',
    ],

    // Trang kết quả công khai và màn xem lượt
    'result' => [
        'title' => 'Kết quả định hướng nghề nghiệp',
        'code' => 'Mã Holland của bạn',
        'code_hint' => 'Ba nhóm có điểm cao nhất, theo thứ tự từ nổi bật nhất.',
        'top' => 'Nhóm nổi bật',
        'scores' => 'Điểm theo 6 nhóm',
        'not_answered' => 'Chưa có câu trả lời',
        'majors' => 'Ngành gợi ý cho bạn',
        'majors_empty' => 'Thông tin đang được cập nhật.',
        'learn_more' => 'Tìm hiểu thêm',
        'note' => 'Kết quả chỉ mang tính tham khảo, giúp bạn hình dung sở thích nghề nghiệp. Hãy kết hợp với năng lực, giá trị cá nhân và tư vấn tuyển sinh.',
        'no_result' => 'Lượt này chưa có kết quả định hướng nghề nghiệp.',
    ],

    // Thống kê
    'stats' => [
        'title' => 'Định hướng nghề nghiệp',
        'by_code' => 'Theo mã Holland',
        'by_group' => 'Theo nhóm nổi bật nhất',
        'by_school' => 'Theo trường',
        'code' => 'Mã',
        'group' => 'Nhóm',
        'school' => 'Trường',
        'count' => 'Số lượt',
        'percent' => 'Tỷ lệ',
        'no_school' => 'Không khai báo',
        'no_data' => 'Chưa có lượt nào có kết quả định hướng nghề nghiệp.',
        'result_column' => 'Mã Holland',
    ],
];
