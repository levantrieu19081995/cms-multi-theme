# Định hướng nghề nghiệp (RIASEC) — cụm chức năng riêng, dùng chung bộ máy khảo sát

Cập nhật: 2026-11-10. Định hướng nghề nghiệp là **một cụm chức năng riêng như Khảo sát** (menu, địa chỉ, quyền, công tắc, trang công khai riêng)
nhưng không viết lại gì: dùng chung model, service, trình dựng câu hỏi, trang làm bài, phản hồi, thống kê, xuất Excel của Khảo sát.

## Hai cụm, một bộ máy (`Modules/CmsBackend/app/Support/SurveyCluster.php`)
| | Khảo sát | Định hướng nghề nghiệp |
|---|---|---|
| Quản trị | `/cms/khao-sat` — route `cms.khao-sat.*` | `/cms/dinh-huong-nghe-nghiep` — route `cms.nghe-nghiep.*` |
| Công khai | `/khao-sat/{mã}` — `cms.frontend.khaosat.*` | `/dinh-huong-nghe-nghiep/{mã}` — `cms.frontend.nghenghiep.*` |
| Loại khảo sát (`ks_loai`) | general, satisfaction, feedback, research | `career` (luôn là career, không có ô chọn loại) |
| Công tắc (Developer Settings > Chức năng) | `survey` | `career` (độc lập, không cần `survey` bật) |
| Quyền | `khaosat-*` (10 quyền) | `nghenghiep-*` (10 quyền: manager, view, create, update, publish, delete, response-view, response-delete, statistics-view, export) |
| Menu quản trị | "Quản lý khảo sát" | "Định hướng nghề nghiệp" |
| Chỉ có ở cụm này | — | "Nhóm & ngành nghề" (`/cms/dinh-huong-nghe-nghiep/nhom-nganh`) |

- Cụm được suy ra từ **tên route đang chạy**; một khảo sát thuộc cụm nào do `ks_loai`. Mã dùng chung chỉ gọi `SurveyCluster::name()/route()/permission()/title()`.
- Route quản trị đăng ký từ một khai báo duy nhất trong `Modules/CmsBackend/routes/web.php` (hàm `$surveyCluster`), route công khai từ một vòng lặp trong
  `Modules/CmsFrontend/routes/public.php`.
- Middleware `survey.cluster` giữ mỗi khảo sát đúng cụm: mở nhầm cụm bằng GET (liên kết/dấu trang cũ như `/cms/khao-sat/22/chinh-sua`, `/khao-sat/{mã career}`) thì chuyển
  sang đúng cụm; ghi dữ liệu nhầm cụm trả 404. Cụm Khảo sát từ chối loại `career`; cụm nghề nghiệp luôn tạo loại `career`.

## Cách hoạt động
1. **Câu hỏi** do quản trị dựng trong tab "Câu hỏi" như thường, chỉ có hai kiểu *Thang điểm* và *Có/Không*, mỗi câu hỏi đang bật chọn một nhóm **R, I, A, S, E hoặc C**
   (lưu ở `ks_cau_hoi.ch_cau_hinh.riasec`). **Phát hành** cần câu hỏi thuộc ít nhất 2 nhóm khác nhau.
2. **Tính điểm** (`Support/Riasec.php`, thuần logic, có test không cần DB): câu trả lời chuẩn hóa về 0..1 (thang `(giá trị − min)/(max − min)`, Có = 1, Không = 0); điểm nhóm = trung bình
   các câu đã trả lời × 100; **mã Holland** = tối đa 3 nhóm điểm cao nhất, bằng điểm thì theo thứ tự R, I, A, S, E, C.
3. **Lưu kết quả** khi gửi bài (cùng transaction): mã Holland → `ks_khao_sat_users.ksu_ket_qua`, điểm từng nhóm (JSON) → `ksu_diem`.
4. **Trang kết quả** = trang cảm ơn: mã Holland, mô tả các nhóm nổi bật, biểu đồ 6 nhóm (HTML/CSS, không cần Vite), ngành gợi ý. Chưa có ngành → "Thông tin đang được cập nhật."
5. **Ngành gợi ý** xếp theo độ phù hợp: mỗi chữ trùng được `(3 − vị trí ở mã người làm) × (3 − vị trí ở mã ngành)`; chỉ lấy ngành đang bật có điểm > 0, tối đa 6.
6. **Trường**: nếu tùy chọn hệ thống `school_list` (JSON `[{key, name}]`) có dữ liệu, trang giới thiệu hỏi "Trường bạn đang học" (không bắt buộc), lưu vào `school_key/school_name`.

## Quản trị (không có gì viết cứng)
- **Định hướng nghề nghiệp > Nhóm & ngành nghề**: sửa tên/mô tả 6 nhóm (để trống = chữ mặc định `lang/*/career.php`), thêm/sửa/ẩn/xóa ngành (tên, mã RIASEC 1–3 chữ, mô tả, liên kết `http(s)`, thứ tự).
- Danh sách/tạo/sửa/phát hành/nhân bản/xóa bài, phản hồi (cột "Mã Holland"), thống kê (theo mã, theo nhóm nổi bật nhất, theo trường), xuất Excel (thêm cột Mã Holland và Trường) — cùng màn với Khảo sát nhưng
  chỉ hiện bài loại career.
- Biểu đồ "Thống kê khảo sát định hướng nghề nghiệp" ở bảng điều khiển thuộc công tắc `career`.

## Developer Settings (cách bật cho một bản triển khai)
1. **Tab Chức năng**: có dòng "Định hướng nghề nghiệp (RIASEC)" (`career`) với công tắc riêng; tắt thì cả quản trị lẫn trang công khai trả 404, dữ liệu giữ nguyên.
2. **Tab Quyền** (manifest quyền của dự án): bộ `nghenghiep-*` là quyền mới nên phải **bật trong Developer Settings** nếu bản triển khai có `permission-manifest.json` (không bật thì mọi người,
   kể cả "Quản trị", đều bị 403 ở cụm này); sau đó gán quyền cho vai trò ở màn Vai trò. Chưa có manifest thì mọi quyền khả dụng.
3. Trên database cần có các quyền: tạo bằng `PermissionSeeder` (hoặc `firstOrCreate` theo `PermissionsEnum` + `parentChildren`).

## Dữ liệu khởi đầu (tùy chọn)
`php artisan survey:career-starter [--dry-run]` tạo MỘT bài *Nháp* (6 phần × 2 câu thang 1–5) và 7 ngành mẫu để quản trị chỉnh trong giao diện; không phát hành, không ghi đè.

## Song ngữ (VI/EN)
- **Chữ giao diện** đủ hai ngôn ngữ, khớp khóa (`lang/{vi,en}/career.php`, `admin.php`, `survey.php`, `features.php`). Quản trị chỉ thấy bản EN khi chức năng **Đa ngôn ngữ** đang bật và chọn English.
- **Nội dung do quản trị nhập** (tên bài, câu hỏi, ngành, tên nhóm đã chỉnh) là một ngôn ngữ (chưa có bảng bản dịch). Theo `config/locales.php` → `default_only_routes`
  (`cms.frontend.khaosat.`, `cms.frontend.nghenghiep.`), trang công khai chỉ có địa chỉ mặc định; trên trang tiếng Anh, liên kết tới hai cụm này dẫn tới thông báo "đang cập nhật".

## Database (migration cộng thêm `2026_11_10_000001_create_career_orientation_tables`)
`ks_khao_sat_users.ksu_diem` (text JSON), `ks_career_group` (tên/mô tả nhóm do quản trị chỉnh), `ks_career_major` (ngành gợi ý, xóa mềm). Chạy lại an toàn, không FK, không dữ liệu trong migration.

## Kiểm thử
`tests/Unit/Career/RiasecTest.php` (8, không DB) và `tests/Feature/Career/CareerOrientationTest.php` (20, transaction rồi rollback trên DB dev):
`ALLOW_DEV_DB_TRANSACTIONAL_TESTS=1 DB_DATABASE=cms_workspace CMS_DB_DATABASE=cms_workspace php artisan test tests/Feature/Career`.
Có test cho: tách cụm (danh sách, chuyển hướng, 404, ép loại), quyền riêng `nghenghiep-*` tách khỏi `khaosat-*`, công tắc `career` độc lập với `survey`, mục Developer Settings, menu, song ngữ.

## Việc chủ đầu tư/trường cần quyết định
- Bộ câu hỏi chính thức và số câu mỗi nhóm (bộ khởi đầu chỉ có 12 câu; bộ chuẩn như O*NET Interest Profiler có 60 câu).
- Danh sách ngành thật và mã RIASEC từng ngành; danh sách trường (`school_list`) và nơi quản trị chỉnh nó (hiện chỉ đọc từ tùy chọn hệ thống).
