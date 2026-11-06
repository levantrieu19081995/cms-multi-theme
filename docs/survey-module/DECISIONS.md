# Quyết định xuyên phase

> **Lưu ý:** chỉ phần quyết định cũ về *theme gating* (D6, D13: `config/features.php`, `ThemeFeatures`, `RequireThemeFeature`, `cms.feature:khao-sat`) đã bị **D27 thay thế**. D27 (Feature Registry: definition trong `Features/Definitions`, trạng thái ở `feature_settings`, middleware `cms.feature:<feature-key>`, theme chỉ lo giao diện) là **quyết định hiện hành**; các quyết định còn lại vẫn có hiệu lực.


| # | Quyết định | Lý do | Ảnh hưởng |
|---|---|---|---|
| D1 | Dữ liệu khảo sát không có `project_key`/`theme_key`; không lọc theo theme. (Module mạng xã hội/bài viết có `project_key` — Khảo sát cố ý không theo.) | 1 khách = 1 theme = 1 DB. | Truy vấn khảo sát không có điều kiện project/theme. |
| D2 | Migration chỉ cộng thêm, đặt ở `database/migrations` (thư mục được nạp), có guard `hasTable/hasColumn`. Không FK. Không `->change()`. | Bảng `ks_*` đã tồn tại ngoài sổ migration; `CMS_REGISTER_MIGRATIONS=false`. | Chạy lại an toàn; migration cũ không đụng. |
| D3 | Trạng thái nghiệp vụ: `ks_status` (draft/open/paused/ended); `ks_trang_thai` bool giữ làm cờ tương thích = `status==open`. | Bool cũ không đủ 4 trạng thái. | Service đồng bộ 2 cột. |
| D4 | `ks_khao_sat_users` = lượt tham gia. Họ tên/SĐT NOT NULL → lưu `''` khi ẩn danh/không khai; hiển thị "Ẩn danh" theo `ksu_an_danh`. | Không sửa cột cũ. | |
| D5 | Mã ổn định: UUID cho câu hỏi (`ch_ma`), lựa chọn (`trl_ma`), nhóm (`nhom_ma`), lượt (`ksu_ma`); slug (`ks_ma`) cho khảo sát. Form công khai & JS dùng các mã này, không dùng 1..N hay ID tự tăng. | Yêu cầu Phase 4. | |
| D6 | Khả dụng theo theme: cấu hình `cmsbackend.features.khao-sat` (theme nào bật) → `PermissionAvailability::isAvailableSlug` coi mọi slug `khaosat-*` là không khả dụng khi theme tắt; thêm middleware `cms.feature:khao-sat` cho route công khai và quản trị. Nguồn: theme công khai hiện hành (`ScreenTheme::key('public')`). | Menu, `User::can`, middleware quyền, màn hình vai trò đều đã đi qua `PermissionAvailability` ⇒ một điểm chặn. | Đổi theme không đụng dữ liệu. |
| D7 | Xóa an toàn bằng soft delete (`*_deleted_at`); câu hỏi/lựa chọn đã có trả lời không xóa vật lý, chỉ "ngưng" (soft delete). | Bảo vệ dữ liệu. | |
| D8 | Khóa cấu trúc: Nháp sửa toàn bộ; Đang mở/Tạm dừng chỉ sửa thông tin chung (không thêm/xóa/đổi câu hỏi, lựa chọn); Kết thúc chỉ xem. Thay đổi lớn → Nhân bản. Chuyển trạng thái chỉ đi tới: draft→open, open↔paused, open/paused→ended. | Quy tắc đề bài. | |
| D9 | Không thêm Vite entry: giao diện mới dùng CSS/JS nội tuyến + `/assets/plugin/sortable/sortable.min.js` có sẵn; trang công khai có layout độc lập. | Không được build. | Theme có thể ghi đè view `pages/khao-sat/*`, fallback về `default`. |
| D10 | Model giữ ở `Modules\CmsFrontend\Models` (như cũ); Service/DataProvider/Controller quản trị ở CmsBackend, Controller + Service công khai ở CmsFrontend (dùng lại DataProvider của CmsBackend). | Tương thích code cũ. | |
| D11 | Quyền: giữ `khaosat-manager`, `khaosat-view`; thêm 8 quyền con (xem Phase 3), tất cả là con của `khaosat-manager` theo quy ước dự án (quyền cha bao trùm con). Không tự gán quyền mới cho vai trò nào; seeder chỉ tạo permission. `khaosat-view` cũ vẫn xem được danh sách phản hồi (tương thích). | Quy tắc đề bài. | Người dùng cần chạy `PermissionSeeder`. |
| D12 | Dashboard (biểu đồ nghề nghiệp) chỉ tính lượt có `ksu_ket_qua`. | Không để khảo sát chung làm lệch số liệu cũ. | |
| D13 | Mặc định bật Khảo sát cho `nam-binh-duong`, `ban-de-an`; **tắt** cho `default` (Khuyến nông). Cấu hình ở `Modules/CmsBackend/config/features.php`. | Khảo sát cũ là mảnh của nguồn Cao đẳng nghề; Khuyến nông là nguồn riêng. Thay đổi là một dòng cấu hình. | Mục menu Khảo sát biến mất ở theme `default`. |
| D14 | 8 quyền con đều là con của `khaosat-manager` (nhóm đúng ở màn hình vai trò) dù theo quy ước dự án quyền cha bao trùm con; quyền mới không tự gán cho vai trò; manifest Developer vẫn phải bật chúng. | Giữ nhóm quyền rõ ràng + tương thích; không tự cấp. | Xem rủi ro Phase 3. |
| D15 | Route quản trị dùng POST thay PUT/DELETE; route công khai ghi dữ liệu cũng POST. | Edge production từ chối method-override. | |
| D16 | Xuất chi tiết mặc định không có họ tên/SĐT/email; chỉ khi bật tường minh `contact=1` (vẫn cần `khaosat-export`) và mỗi lần xuất được ghi log. Không bao giờ xuất IP băm, dấu vết trình duyệt, user-agent. Ô bắt đầu bằng `= + - @` được thêm `'`. | "Không xuất trường nhạy cảm không cần thiết"; chống formula injection. | |
| D17 | Xóa phản hồi = xóa mềm (`ksu_deleted_at`); câu trả lời giữ nguyên nhưng không được tính/hiển thị. Cho phép ở mọi trạng thái khảo sát (quyền `khaosat-response-delete`). | Không xóa dữ liệu vĩnh viễn; xử lý spam. | |
| D18 | Chống gửi lặp theo trình duyệt (cookie + HMAC), không theo IP; IP và cookie chỉ lưu dạng HMAC. Lượt đang làm dở được dùng lại trong 24 giờ. | Riêng tư + không chặn nhầm nhiều người chung NAT. | Có thể bị vượt bằng xóa cookie (chấp nhận). |
| D19 | Bảng điều khiển cũ (biểu đồ nghề nghiệp) vẫn mở được khi chưa chạy migration: `KhaoSatDataProvider::getUsersWithFilter` bỏ scope xóa mềm nếu thiếu cột `ksu_deleted_at`. | Thứ tự triển khai code → migration không được làm hỏng trang đầu của admin. | Các màn hình khảo sát mới vẫn cần migration. |
| D20 | Test khảo sát chỉ chạy trên SQLite `:memory:` (tự skip nếu DB khác) và tự dựng schema bằng chính các migration mới. | Không chạm DB thật. | Cần bật `pdo_sqlite` khi chạy. |
| D21 | HTML giới thiệu lọc bằng HTMLPurifier (allow-list) lúc lưu **và** lúc hiển thị; không dùng regex. | KS-001. | Phụ thuộc gián tiếp trong vendor. |
| D22 | Danh sách khảo sát là điểm vào chung cho mọi quyền khảo sát; hành động trong danh sách theo từng quyền. | KS-002; không mở rộng quyền ghi. | |
| D23 | Phát hành dựa trên schema hiển thị thực tế; câu hỏi lựa chọn bật phải có ≥2 lựa chọn bật. | KS-003. | |
| D24 | Bắt đầu/gửi khảo sát khóa dòng khảo sát (`FOR UPDATE`) để giới hạn số lần gửi là nguyên tử. | KS-004. | Tuần tự hóa theo khảo sát. |
| D25 | Payload cấu trúc: ID không trùng, thuộc đúng khảo sát; vượt giới hạn độ dài = lỗi, không cắt. | KS-008. | |
| D26 | Audit thao tác nhạy cảm qua `Log::info('survey.audit')`, không ghi dữ liệu cá nhân. | KS-009. | |

## D27 — Feature Registry thay công tắc theme→khảo sát (thay thế KS-005/D-registry cũ)
- **Quyết định:** `config/features.php`, `Support/ThemeFeatures` và middleware `RequireThemeFeature` bị gỡ. Bật/tắt chức năng nay do **Feature Registry** (`Modules/CmsBackend/app/Features`) quyết định: definition trong code (`Definitions/SurveyFeature.php`, mã `survey`) + bảng `feature_settings` của bản triển khai. **Theme không còn quyết định chức năng nào**; theme chỉ đổi giao diện. Dữ liệu khảo sát vẫn không gắn theme/`project_key`.
- **Cổng route:** `cms.feature:survey` (middleware `RequireFeature`) cho cả `/cms/khao-sat/*` và `/khao-sat/*` → 404 khi tắt; dữ liệu giữ nguyên. Quyền `khaosat-*` coi như không khả dụng khi chức năng tắt (`PermissionAvailability`).
- **Bật/tắt:** chỉ ở Developer Settings → tab Feature Registry (phiên Developer đã mở khóa, CSRF, `throttle:10,1`, ghi Developer Audit).
- **Menu:** loại mới "Chức năng hệ thống" (`menu_items.type = 'feature'`, `feature_key`, `feature_target`); URL luôn sinh từ route trong Registry, không lưu URL làm nguồn chính. Chức năng tắt/thiếu → ẩn ngoài website, giữ trong trình quản lý kèm cảnh báo.
- **Mở rộng:** thêm chức năng = thêm một class vào `Definitions/` (không sửa Registry/Menu).
- **Tài liệu cũ:** các mục trong `03-PHASE-3-PERMISSIONS.md` và `D-*` nói về `config/features.php`/theme gating nay đã lỗi thời; `verification/verify_phase3.php` bị xóa vì gọi các class đã gỡ (được thay bằng `tests/Feature/Survey`).
