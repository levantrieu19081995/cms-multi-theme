# Deploy một theme lên server

Mỗi server chạy MỘT theme và có `.env` + database riêng: `knct.cusc.vn` = `default` (Khuyến nông). Dự án không phải git repo nên gói deploy là **toàn bộ code**, không phải danh sách tệp đã sửa (tránh sót).

## Máy dev (người hoặc agent)
1. `powershell -File scripts/deploy-package.ps1 -DryRun` — kiểm tra registry asset, cú pháp PHP, liệt kê migration. Phải đạt hết.
2. `powershell -File scripts/deploy-package.ps1 -Theme default` — build đúng theme và tạo `build-exports/deploy/<theme>-<giờ>.zip` kèm `DEPLOY-STEPS.txt`.
3. Upload zip lên server, giải nén ra thư mục tạm. **Không** upload `.env`, `vendor/`, `storage/`.

## Server
Làm đúng `DEPLOY-STEPS.txt` trong gói (sao lưu → đè code → thay `public/build` → `.env` → migration → `homepage:layout-install` → xóa cache → kiểm tra).

## `.env` của server phải có
- `DEVELOPER_ACCESS_TOKEN_HASH='$2y$12$...'` (bcrypt, trong dấu nháy đơn). Thiếu biến này thì `/cms/developer-settings` trả 404 theo thiết kế.
  Tạo mã: `php -r "echo password_hash('MẬT_KHẨU', PASSWORD_BCRYPT);"`. Agent không tự chọn mật khẩu thay người dùng.
- `APP_KEY`, `DB_*` giữ nguyên của server. Chạy `php artisan config:clear` (hoặc `config:cache`) sau khi sửa.

## Truyền thông đa kênh (Facebook, Zalo OA)
- Facebook: `META_APP_ID`, `META_APP_SECRET`, `META_GRAPH_VERSION`, `META_REDIRECT_URI`; callback `https://<domain>/cms/truyen-thong/tai-khoan/facebook/callback` đăng ký trong Meta App. Đăng bài cần quyền `pages_manage_posts` (thường qua App Review) và app ở chế độ Live.
- Zalo OA: `ZALO_APP_ID`, `ZALO_APP_SECRET`, `ZALO_REDIRECT_URI`; Callback URL `https://<domain>/cms/truyen-thong/tai-khoan/zalo/callback` đăng ký trong ứng dụng trên Zalo Developers. Ứng dụng cần quyền "Quản lý thông tin OA" và "Quản lý bài viết"; một số tính năng yêu cầu OA đã xác thực hoặc có hạn mức theo gói.
- Mỗi biến để trống thì kênh đó tắt (thẻ báo "chưa cấu hình"). Sau khi sửa `.env` chạy `php artisan config:clear`.
- Migration `2026_11_11_000001_add_metrics_to_social_publication_channels_table` thêm cột `metrics` (số liệu tương tác). Chưa chạy thì mọi chức năng vẫn dùng được, chỉ không lưu và không hiển thị số liệu.
- Gửi bài chạy bằng job. `QUEUE_CONNECTION=sync` gửi ngay trong request; dùng hàng đợi `database` thì phải chạy `php artisan queue:work`. Job chỉ chạy một lần: bài lỗi được gửi lại khi người dùng bấm "Đăng lại", không tự thử lại.

## Quy tắc cho agent
- Không sửa `.env` của server, không chạy `migrate:fresh`, không chạy toàn bộ `vendor/bin/phpunit` trên DB có dữ liệu.
- Mỗi gói chỉ chứa build của MỘT theme; không trộn file build của hai theme.
- Đổi cấu trúc dữ liệu = thêm migration trong `database/migrations/` (tệp tự kiểm tra bảng/cột đã có). Không sửa tay DB.
- Báo rõ bước nào đã làm, bước nào chưa (build thật, migration, kiểm tra trình duyệt).

## Migration: `migrate` thường
Database được nhập từ nguồn có sẵn, nên các migration cũ (2024-2025, tạo bảng nền) đang là Pending dù bảng đã tồn tại; `php artisan migrate` thẳng sẽ lỗi ở migration đầu tiên. Dùng lệnh `migrate:baseline` một lần cho mỗi database có sẵn:
1. `php artisan migrate:baseline` — chạy thử, chỉ in danh sách migration cũ sẽ được đánh dấu (chỉ những cái mà mọi bảng nó tạo đã tồn tại).
2. `php artisan migrate:baseline --force` — ghi tên các migration đó vào bảng `migrations`. Không chạy migration nào, không đổi bảng dữ liệu nào.
3. Từ đó `php artisan migrate --force` chỉ chạy các migration mới. Sao lưu database trước.

## Máy chủ mới (database trống)
Các bảng như `ks_khao_sat`, `lienhe`, `doi_tac`, `posts_banners` đến từ nguồn có sẵn và không có migration tạo, nên migration một mình không dựng được database trống. Cách làm: nhập (import) bản sao cấu trúc database mẫu của dự án vào database mới, rồi chạy các bước `migrate:baseline` + `migrate` ở trên. Có thể tạo bản sao cấu trúc bằng `php artisan schema:dump` (cần `mysqldump`) từ database đã `migrate:baseline` đầy đủ; Laravel sẽ tự nạp `database/schema/mysql-schema.sql` khi database chưa có bảng `migrations`.

