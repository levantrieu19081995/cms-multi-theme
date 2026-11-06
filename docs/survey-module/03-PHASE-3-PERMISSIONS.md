# Phase 3 — Phân quyền và khả dụng theo theme

> **⚠ ĐÃ BỊ THAY THẾ (D27).** Mọi nội dung trong tài liệu này về `config/features.php`, `ThemeFeatures`, `RequireThemeFeature`, `cms.feature:khao-sat` hoặc "chức năng phụ thuộc theme" là **lỗi thời**. Cơ chế chính thức là **Feature Registry**:
> - Definition ở `Modules/CmsBackend/app/Features/Definitions/` (khảo sát: `SurveyFeature`, mã `survey`);
> - Trạng thái bật/tắt của bản triển khai nằm ở bảng `feature_settings`;
> - Route dùng middleware `cms.feature:<feature-key>` (ví dụ `cms.feature:survey`);
> - Theme chỉ phụ trách giao diện, không quyết định chức năng; dữ liệu nghiệp vụ không gắn theme.
> Xem `DECISIONS.md` mục D27.


## 1. Phạm vi đã thực hiện
- Thêm 8 quyền con Khảo sát, giữ `khaosat-manager`/`khaosat-view`.
- Cổng khả dụng theo theme công khai hiện hành: ẩn menu, chặn route, vô hiệu hóa quyền nghiệp vụ, chặn bảng điều khiển đọc dữ liệu khảo sát.
- Hiển thị trạng thái "Tắt theo theme" trong Developer Settings; nhóm quyền hiển thị đúng theo `parent_id` ở màn hình vai trò.

## 2. Kiến trúc được chọn (một điểm quyết định, nhiều lớp thực thi)
`config/features.php` (`cmsbackend.features`) khai báo: chức năng `khao-sat`, tiền tố quyền `khaosat-`, danh sách theme bật (`nam-binh-duong`, `ban-de-an`; `default` tắt). `Support\ThemeFeatures` trả lời "chức năng này có bật với theme hiện hành (`ScreenTheme::key('public')`) không".

Các lớp chặn (backend, không chỉ ẩn menu):
1. **`PermissionAvailability::isAvailableSlug`**: slug có tiền tố `khaosat-` → *không khả dụng* khi theme tắt (đặt trước kiểm tra manifest, sau `CORE`). Vì mọi nơi hỏi quyền đều đi qua lớp này nên đồng thời có hiệu lực ở:
   - `User::hasPermissionTo` / `can()` / `@can` (kể cả vai trò Quản trị, đúng quy tắc sẵn có "no role bypasses it"),
   - middleware `cms.check.permission` (`CDNCheckPermission`) và `check.permission`,
   - **menu** (`ProjectMenu` lọc theo `User::getAllPermissions()` đã qua `filter()`),
   - màn hình vai trò (`RoleController`, `Api\RoleController` dùng `filter()`/`unavailableIds()`/`syncRole()`: không gán được quyền không khả dụng; quyền đang bị ẩn không bị gỡ khỏi vai trò khi lưu — dữ liệu phân quyền không mất khi đổi theme),
   - Developer Settings (`overview()` có cờ `theme_blocked`, hiển thị huy hiệu "Tắt theo theme").
2. **Middleware `cms.feature:khao-sat`** (`RequireThemeFeature`, alias trong `RouteServiceProvider`): mọi route `cms/khao-sat*` (và route công khai ở Phase 4) trả **404** khi theme tắt — kể cả khi người gọi biết URL hoặc có quyền.
3. **Dashboard**: `CmsBackendController::getKhaoSatStats` không đọc dữ liệu khảo sát khi theme tắt.

Đổi theme chỉ đổi kết quả của `ThemeFeatures::enabled()`; không có câu lệnh nào đọc/ghi bảng `ks_*` trong cơ chế này.

## 3. Danh sách file thêm mới
- `Modules/CmsBackend/config/features.php`
- `Modules/CmsBackend/app/Support/ThemeFeatures.php`
- `Modules/CmsBackend/app/Http/Middleware/RequireThemeFeature.php`
- `docs/survey-module/verification/verify_phase3.php`

## 4. Danh sách file chỉnh sửa
- `Modules/CmsBackend/app/Enums/PermissionsEnum.php` (8 case, nhãn, `parentChildren`).
- `Modules/CmsBackend/app/Services/Developer/PermissionAvailability.php` (cổng theme + cờ `theme_blocked`).
- `Modules/CmsBackend/app/Providers/RouteServiceProvider.php` (alias `cms.feature`).
- `Modules/CmsBackend/routes/web.php` (middleware quyền/theme theo từng route — Phase 2).
- `Modules/CmsBackend/resources/menu/project-verticalMenu.json` (tên mục + `roler` mở rộng).
- `Modules/CmsBackend/app/Http/Controllers/CmsBackendController.php` (dashboard).
- `.../developer-settings/index.blade.php`, `lang/vi/admin.php`, `lang/en/admin.php`.

## 5. Migration đã viết
Không có migration cho quyền. Permission là dữ liệu do `PermissionSeeder` tạo từ enum (idempotent `firstOrCreate`, gán `slug`, `parent_id`).

## 6. Quan hệ dữ liệu
`permissions.parent_id`: 8 quyền con → `khaosat-manager`.

## 7. Route
Không thêm route mới ở Phase này; middleware `cms.feature:khao-sat` + `cms.check.permission:<nhãn>` được áp lên mọi route khảo sát (bảng ở Phase 2).

## 8. Permission mới

| Slug | Nhãn (cột `name`) | Dùng cho |
|---|---|---|
| `khaosat-manager` (cũ, cha) | Quản lý khảo sát | bao trùm các quyền con (quy ước dự án) |
| `khaosat-view` (cũ) | Xem kết quả khảo sát | danh sách/xem khảo sát; **tương thích**: vẫn xem danh sách & chi tiết phản hồi |
| `khaosat-create` | Thêm khảo sát | tạo, nhân bản |
| `khaosat-update` | Cập nhật khảo sát | sửa thông tin, trình dựng |
| `khaosat-publish` | Phát hành khảo sát | mở/tạm dừng/kết thúc |
| `khaosat-delete` | Xóa khảo sát | xóa mềm/khôi phục |
| `khaosat-response-view` | Xem phản hồi khảo sát | danh sách + chi tiết lượt tham gia |
| `khaosat-response-delete` | Xóa phản hồi khảo sát | xóa mềm lượt tham gia |
| `khaosat-statistics-view` | Xem thống kê khảo sát | thống kê |
| `khaosat-export` | Xuất dữ liệu khảo sát | xuất Excel |

**Không tự cấp cho vai trò nào.** Quyền mới chỉ tồn tại sau khi người dùng chạy `PermissionSeeder`; vai trò hiện có chỉ giữ quyền đã có. Lưu ý quy ước sẵn có của dự án: *người giữ quyền cha `khaosat-manager` được coi là có mọi quyền con* (`CDNCheckPermission` mở rộng cha→con). Do đó chủ sở hữu `khaosat-manager` hiện tại sẽ dùng được các chức năng mới. Nếu cần tách riêng (vd. chỉ `export`), phải cấp quyền con lẻ cho vai trò thay vì cấp `khaosat-manager` (xem rủi ro).

**Manifest Developer**: nếu triển khai đang có `permission-manifest.json` hợp lệ, 8 slug mới **chưa khả dụng cho đến khi Developer bật** trong Developer Settings (hành vi sẵn có của `PermissionAvailability`). Sau khi theme bật Khảo sát, vẫn cần bước này.

## 9. Giữ lại từ code cũ
Toàn bộ cơ chế `PermissionAvailability`, `CDNCheckPermission`, `ProjectMenu`, Developer Settings; slug và nhãn `khaosat-manager`/`khaosat-view` không đổi (cột `name` là khóa tra cứu nên **không đổi nhãn cũ**).

## 10. Không sử dụng và lý do
- Không thêm `theme_key`/`project_key` vào dữ liệu; không lọc truy vấn khảo sát theo theme.
- Không thêm bộ lọc theo theme trong DataProvider.
- Không dùng `ProjectContext` (gắn với bài viết/mạng xã hội) cho khảo sát.

## 11. Rủi ro còn lại
- Mặc định `default` (Khuyến nông) **tắt** Khảo sát (trước đây mục menu hiện với mọi theme). Muốn bật chỉ cần thêm `'default'` vào `themes` trong `features.php` (xem DECISIONS D13).
- Quyền cha bao trùm quyền con: người giữ `khaosat-manager` có luôn `export`/`delete`.
- Nếu `ScreenTheme::key()` ném lỗi (ví dụ chưa có DB) thì `ThemeFeatures` coi như bật (fail-open) — chỉ xảy ra khi cả hệ thống quyền cũng không hoạt động.
- Menu có cache bản chưa lọc (`cms_twin_menu`): sau khi triển khai nên `php artisan optimize:clear` để thấy mục menu đổi tên/roler.
- `khaosat-view` vẫn truy cập danh sách phản hồi (tương thích) — đây là quyết định có chủ đích, không phải lỗ hổng.

## 12. Việc chưa hoàn thành
Chưa kiểm tra trên giao diện Role/Developer thật (cần DB đã seed).

## 13. Hướng dẫn kiểm tra
Chạy `DB_CONNECTION=sqlite DB_DATABASE=:memory: php -d extension=pdo_sqlite -d extension=sqlite3 -d auto_prepend_file=docs/survey-module/verification/prepend.php artisan tinker --execute="require 'docs/survey-module/verification/verify_phase3.php';"` (ma trận theme × manifest: vắng / liệt kê đủ / chỉ 2 quyền cũ; middleware 404; mọi route `cms.khao-sat*` có auth + feature + permission). Kết quả lần chạy cuối: toàn bộ `PASS`.

## 14. Xác nhận
Không build, không migrate, không seed, không deploy. Script dùng SQLite bộ nhớ và file manifest tạm; không ghi vào `storage/app/developer` thật.
