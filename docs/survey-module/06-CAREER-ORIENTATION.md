# Định hướng nghề nghiệp (RIASEC) — phần mở rộng của Khảo sát

Cập nhật: 2026-11-10. Làm trên nền Khảo sát dùng chung (không tạo chức năng riêng): cùng trình dựng câu hỏi, trang công khai,
thống kê, xuất Excel, Feature Registry (`survey`) và quyền `khaosat-*`.

## Cách hoạt động
1. **Loại khảo sát** `ks_loai = career` ("Định hướng nghề nghiệp (RIASEC)"). Loại bị khóa sau khi phát hành/có lượt tham gia (như mọi quy tắc thu thập).
2. **Câu hỏi** do quản trị dựng trong tab "Câu hỏi" như thường, nhưng chỉ có hai kiểu *Thang điểm* và *Có/Không*, và mỗi câu hỏi đang bật
   phải chọn một nhóm **R, I, A, S, E hoặc C**. Nhóm lưu trong `ks_cau_hoi.ch_cau_hinh.riasec`. Khảo sát loại khác bỏ qua/xóa khóa này.
3. **Phát hành** cần mọi câu hỏi đang bật hợp lệ và câu hỏi thuộc ít nhất 2 nhóm khác nhau.
4. **Tính điểm** (`Modules/CmsBackend/app/Support/Riasec.php`, thuần logic, có test không cần DB):
   - câu trả lời chuẩn hóa về 0..1: thang `(giá trị − min) / (max − min)`, Có = 1, Không = 0;
   - điểm nhóm = trung bình các câu đã trả lời × 100 (làm tròn); nhóm không có câu nào được trả lời không có điểm;
   - **mã Holland** = tối đa 3 nhóm điểm cao nhất; bằng điểm thì theo thứ tự R, I, A, S, E, C.
5. **Lưu kết quả** khi gửi bài (cùng transaction với câu trả lời): mã Holland vào cột cũ `ks_khao_sat_users.ksu_ket_qua`, điểm từng nhóm
   (JSON) vào `ksu_diem`. Khảo sát loại khác không ghi hai cột này.
6. **Trang kết quả** = trang cảm ơn (`/khao-sat/{mã}/cam-on`): mã Holland, mô tả các nhóm nổi bật, biểu đồ 6 nhóm (HTML/CSS, không cần Vite),
   ngành gợi ý. Chỉ thấy được từ phiên trình duyệt vừa làm xong bài.
7. **Ngành gợi ý** xếp theo độ phù hợp: mỗi chữ trùng được `(3 − vị trí ở mã người làm) × (3 − vị trí ở mã ngành)`; chỉ lấy ngành đang bật
   có điểm > 0, tối đa 6. Không có ngành nào → trang hiện "Thông tin đang được cập nhật."
8. **Trường**: nếu tùy chọn hệ thống `school_list` (JSON `[{key, name}]`) có dữ liệu, trang giới thiệu hỏi "Trường bạn đang học" (không bắt buộc) và
   lưu vào `school_key`/`school_name`; khóa lạ bị bỏ qua. Thống kê và biểu đồ ở bảng điều khiển gom theo trường.

## Quản trị (không có gì viết cứng)
- **Khảo sát > Nhóm & ngành nghề** (`/cms/khao-sat/nghe-nghiep`): sửa tên/mô tả 6 nhóm (để trống = chữ mặc định trong `lang/*/career.php`) và
  thêm/sửa/ẩn/xóa ngành (tên, mã RIASEC 1–3 chữ cái, mô tả, liên kết `http(s)`, thứ tự). Quyền: xem = `khaosat-view`/`khaosat-update`, ghi = `khaosat-update`
  (không thêm quyền mới, không cần seeder).
- **Phản hồi**: cột "Mã Holland", chi tiết một lượt có biểu đồ điểm; **Thống kê**: theo mã Holland, theo nhóm nổi bật nhất, theo trường;
  **Xuất chi tiết** có thêm cột Mã Holland và Trường.

## Dữ liệu khởi đầu (tùy chọn)
`php artisan survey:career-starter [--dry-run]` tạo MỘT khảo sát *Nháp* (6 phần × 2 câu thang 1–5) và 7 ngành mẫu. Chỉ là điểm xuất phát để
chỉnh trong giao diện; không phát hành, không ghi đè (đã có khảo sát career/ngành thì bỏ qua). Triển khai thật nên thay ngành bằng ngành của trường.

## Database (migration cộng thêm `2026_11_10_000001_create_career_orientation_tables`)
`ks_khao_sat_users.ksu_diem` (text JSON), bảng `ks_career_group` (tên/mô tả nhóm do quản trị chỉnh) và `ks_career_major` (ngành gợi ý, xóa mềm).
Chạy lại an toàn; không FK; không dữ liệu trong migration.

## Kiểm thử
`tests/Unit/Career/RiasecTest.php` (8 test, không DB) và `tests/Feature/Career/CareerOrientationTest.php` (11 test, transaction rồi rollback trên DB dev:
`ALLOW_DEV_DB_TRANSACTIONAL_TESTS=1 DB_DATABASE=cms_workspace CMS_DB_DATABASE=cms_workspace php artisan test tests/Feature/Career`).
Đã thử trong trình duyệt: tạo khảo sát loại career, trình dựng (ô chọn nhóm, báo lỗi khi thiếu nhóm), phát hành, làm bài ở trang công khai, trang kết quả,
quản lý nhóm/ngành, phản hồi, thống kê, xuất Excel, biểu đồ bảng điều khiển.

## Song ngữ (VI/EN) và Developer Settings
- **Chữ giao diện** có đủ hai ngôn ngữ, khớp khóa VI/EN (`lang/{vi,en}/career.php`, phần `career`/`builder`/`error` trong `admin.php`, `survey.php`, `features.php`):
  màn quản trị, trình dựng, thống kê, trang kết quả, tên/mô tả mặc định của 6 nhóm. Quản trị chỉ thấy bản EN khi chức năng **Đa ngôn ngữ** (Developer Settings >
  Feature Registry, mã `multilingual`) đang bật và quản trị chọn English; đang tắt thì chỉ còn tiếng Việt.
- **Nội dung do quản trị nhập** (tên khảo sát, câu hỏi, tên/mô tả nhóm đã chỉnh, ngành gợi ý) là một ngôn ngữ, vì chưa có bảng bản dịch cho khảo sát/ngành.
  Theo quy ước của dự án (`config/locales.php` → `default_only_routes`), trang công khai của Khảo sát chỉ có địa chỉ mặc định (không có `/en/khao-sat`) và trên trang
  tiếng Anh, liên kết tới Khảo sát dẫn tới thông báo "đang cập nhật". Muốn trang công khai song ngữ thật cần thêm bảng bản dịch cho khảo sát/câu hỏi/ngành.
- **Feature Registry** (Developer Settings > tab Chức năng): có mục riêng `career` — "Định hướng nghề nghiệp (RIASEC)" (`Features/Definitions/CareerFeature.php`), nằm cạnh `survey`. Cần `survey` đang bật (route nằm trong nhóm `cms.feature:survey`); `career` là công tắc riêng: tắt thì `/cms/khao-sat/nghe-nghiep` và trang công khai của khảo sát loại career trả 404, khảo sát career không hiện ở danh sách công khai, không tạo được khảo sát loại career mới; khảo sát thường không bị ảnh hưởng và dữ liệu giữ nguyên (có test). Mặc định bật. Chức năng này không có quyền riêng (dùng `khaosat-*` do `survey` sở hữu, nên cũng không thêm gì vào `permission-manifest`) và không có điểm đến menu cố định (mỗi khảo sát có địa chỉ riêng).

## Việc chủ đầu tư/trường cần quyết định
- Bộ câu hỏi chính thức và số câu mỗi nhóm (bộ khởi đầu chỉ có 12 câu; các bộ chuẩn như O*NET Interest Profiler có 60 câu).
- Danh sách ngành thật và mã RIASEC của từng ngành.
- Danh sách trường (`school_list`) và nơi quản trị chỉnh nó (hiện chỉ đọc từ tùy chọn hệ thống).
