# Khuyến nông frontend

Trước khi thiết kế/triển khai/review giao diện, đọc `../../.codex/skills/khuyen-nong-editorial/SKILL.md`, xem ảnh chuẩn và đọc reference liên quan tới công việc. Tuân theo phạm vi trong `../../AGENTS.md`.

- Giữ namespace `khuyennongcanthofrontpage`, Blade server-rendered và Vite hiện có.
- CSS giao diện mới đặt trong module, scope dưới `.knct-public`; phần trang chủ dưới `.knct-home`. Không đổi theme/CSS gốc hoặc toàn bộ selector `body`, `.container`, `.card`, `.btn` để sửa một trang.
- `index.blade.php` ghép section; dữ liệu chuẩn bị ở controller/service/provider. Không nhúng query mới vào Blade.
- Trang chủ chỉ dùng dữ liệu thật theo quyền hiển thị (từ 16/10/2026 không còn chế độ demo/dataset mẫu trong mã nguồn). Nội dung giống bản thiết kế đã được nhập thành bản ghi thật trong DB KNCT, xem `audits/2026-10-15-knct-legacy-posts-migration.md`. Logo gốc do người dùng cung cấp luôn là chuẩn nhận diện, không vẽ lại.
- Thay đổi header/footer phải kiểm tra trang chi tiết và PDF, không chỉ trang chủ. Trường hợp ít dữ liệu phải dùng được.
