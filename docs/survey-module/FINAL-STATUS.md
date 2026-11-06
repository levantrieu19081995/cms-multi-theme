# Trạng thái cuối — Khảo sát dùng chung

> **⚠ ĐÃ BỊ THAY THẾ (D27).** Mọi nội dung trong tài liệu này về `config/features.php`, `ThemeFeatures`, `RequireThemeFeature`, `cms.feature:khao-sat` hoặc "chức năng phụ thuộc theme" là **lỗi thời**. Cơ chế chính thức là **Feature Registry**:
> - Definition ở `Modules/CmsBackend/app/Features/Definitions/` (khảo sát: `SurveyFeature`, mã `survey`);
> - Trạng thái bật/tắt của bản triển khai nằm ở bảng `feature_settings`;
> - Route dùng middleware `cms.feature:<feature-key>` (ví dụ `cms.feature:survey`);
> - Theme chỉ phụ trách giao diện, không quyết định chức năng; dữ liệu nghiệp vụ không gắn theme.
> Xem `DECISIONS.md` mục D27.


**Cập nhật:** 2026-11-01 · **Trạng thái:** ⏳ **Đã đóng các lỗi code qua vòng review; còn nghiệm thu môi trường KS-010.** KS-001 đã khai báo dependency trực tiếp và được kiểm tra lại; KS-002, KS-003, KS-006, KS-007, KS-008, KS-009 đã VERIFIED; KS-005 đã chốt theo thiết kế. **Còn lại:** KS-010 (DB MySQL tạm + trình duyệt) và KS-011 (hạn chế đã biết).

**Kết luận thẳng:** 5 phase đã viết xong mã + hồ sơ và đạt kiểm thử tự động trên SQLite bộ nhớ. **Chưa chạy được trên hệ thống thật**: migration chưa chạy, permission chưa seed, chưa thử bằng trình duyệt. Không nên gọi là "production-ready" trước khi các bước ở mục "Việc người dùng cần làm" và kiểm tra giao diện thực tế hoàn tất.

## Đã hoàn thành
| Phase | Nội dung | Bằng chứng |
|---|---|---|
| 1 | Rà soát + bản ánh xạ schema, quyết định D1–D12 | `01-PHASE-1-AUDIT.md`, `DECISIONS.md` |
| 2 | Danh sách/tạo/sửa/nhân bản/xem trước/xóa an toàn/khôi phục; trình dựng (phần, 6 kiểu câu hỏi, bắt buộc, bật/tắt, nhân bản, kéo thả); vòng đời Nháp→Mở⇄Tạm dừng→Kết thúc; khóa cấu trúc; 4 migration cộng thêm | 30 kiểm tra (`verification/verify_phase2.php`) |
| 3 | 8 quyền con, cổng theme (menu + `User::can` + middleware quyền + route `cms.feature` + dashboard + màn hình vai trò/Developer) | 38 kiểm tra (`verify_phase3.php`) + test route |
| 4 | Trang công khai: giới thiệu, đồng ý/định danh, làm bài theo phần, kiểm tra, gửi idempotent, cảm ơn; rate limit; chống gửi lặp; fallback theme | 16 test |
| 5 | Danh sách/chi tiết phản hồi, thống kê, xuất Excel tổng hợp + chi tiết theo bộ lọc, xóa phản hồi theo quyền | 10 test |

Tổng: `tests/Feature/Survey` = **102 test, 1151 assertion, OK, 0 skipped** (SQLite `:memory:`, PHP 8.3.36) sau vòng review thứ 3.

## Chưa hoàn thành / hạn chế (không giấu)
1. **Chưa chạy migration/seeder** (theo yêu cầu). 4 migration mới ở `database/migrations/2026_10_31_00000{1..4}_*.php`; quyền mới cần `PermissionSeeder`.
2. **Chưa thử bằng trình duyệt**: trình dựng kéo thả (`public/assets/js/survey/builder.js`), trang công khai (tiến độ, bước), giao diện admin mới. Chỉ có kiểm tra HTML sinh ra bằng test và SQLite.
3. ~~Chưa có bản dịch EN~~ — đã bổ sung (KS-006).
4. **Xuất Excel đồng bộ** (không hàng đợi) — cần xem lại nếu khảo sát có hàng chục nghìn lượt.
5. Không có biểu đồ đồ họa (bảng + thanh tiến độ).
6. Chống gửi lặp theo trình duyệt (cookie) — có thể vượt bằng xóa cookie; không xác thực người dùng.
7. Quyền cha `khaosat-manager` bao trùm toàn bộ quyền con theo quy ước dự án (gồm `export`/`delete` mới) — xem D14.
8. Không có placeholder/dữ liệu mẫu hay ID viết cứng trong mã chức năng. Các giá trị cấu hình có chủ đích: danh sách theme bật Khảo sát (`config/features.php`), 4 loại khảo sát (`general/satisfaction/feedback/research`), giới hạn kích thước (30 phần/300 câu hỏi/50 lựa chọn).

## Cần bạn quyết định
- **KS-005:** giữ công tắc theme→khảo sát ở `config/features.php` (một nguồn, đổi bằng sửa file) hay đưa vào Developer Settings/manifest?
- **KS-007:** khóa các trường thu thập (ẩn danh, yêu cầu thông tin, số lần gửi, loại, ngày bắt đầu) sau khi Mở/có phản hồi? (đề xuất: có)
- Thêm `ezyang/htmlpurifier` vào `require` của `composer.json` (hiện chỉ là phụ thuộc gián tiếp).

## Việc người dùng cần làm trước khi dùng (thứ tự gợi ý)
1. Sao lưu DB. Chạy 4 migration mới (chỉ cộng thêm; chạy lại an toàn).
2. Chạy `PermissionSeeder` (tạo 8 quyền con, gán `parent_id`). Nếu triển khai có `permission-manifest.json` hợp lệ: **bật 8 quyền mới trong Developer Settings** (nếu không chúng sẽ "không khả dụng").
3. Quyết định theme nào bật Khảo sát (`Modules/CmsBackend/config/features.php`; mặc định `nam-binh-duong`, `ban-de-an`; `default` tắt — D13). Gán quyền mới cho vai trò cần thiết (không tự gán).
4. `php artisan optimize:clear` (cache menu/config).
5. Kiểm tra bằng trình duyệt: tạo khảo sát → dựng câu hỏi (kéo thả) → xem trước → phát hành → làm khảo sát công khai trên từng theme → xem phản hồi/thống kê → xuất Excel.

## Việc đã chạm tới hệ thống thật (minh bạch)
- Phase 1: vài lệnh `artisan tinker` **chỉ đọc** DB `cms_workspace` (`Schema::getColumns`, `count`, tên permission) và `route:list`.
- Mọi lệnh `artisan`/`phpunit` của dự án đi qua `AppServiceProvider::boot`, vốn tự tăng **bộ đếm lượt truy cập** (`visits`) với request GET không phải bot. Các lệnh ở Phase 1 (trước khi phát hiện) có thể đã tăng bộ đếm này vài đơn vị; về sau dùng `docs/survey-module/verification/prepend.php` (User-Agent giả bot) để tránh.
- Chạy các test **có sẵn** của dự án (`PhaseOneIsolationTest`, `ScreenTheme*`, `Sbut*`, `BanDeAn*`): một phần dùng SQLite bộ nhớ, `PhaseOneIsolationTest` đọc DB thật (không ghi). Nhiều test trong số đó **lỗi** (302/403, số lượng theme, tiền tố `/cms`); không có git để đối chiếu trước/sau, nên **chưa chứng minh** chúng đã lỗi từ trước. Một test (`test_administrator_can_open_existing_management_pages`) từng lỗi do thay đổi của tôi (SELF-2) và đã được sửa; các lỗi còn lại không chạm mã khảo sát (đường dẫn kiểm tra: 302 trang chủ, 403 đổi theme bị khóa, số theme đã đăng ký). Reviewer nên chạy lại bộ test cũ trước/sau để xác nhận.
- Không có thay đổi dữ liệu hay schema nào trên DB thật; không build; không deploy; không sao chép module `CaoDangNgheSocTrang`.

## Danh sách tài liệu
`00-MASTER-PLAN.md` · `01…05-PHASE-*.md` · `DECISIONS.md` (D1–D20) · `CLAUDE-FIX-LOG.md` · `verification/` (script kiểm chứng + `prepend.php`).

## Khi có `CODEX-REVIEW.md`
Xử lý lỗi `OPEN` từ nghiêm trọng đến nhẹ, ghi từng lỗi vào `CLAUDE-FIX-LOG.md`, không sửa `CODEX-REVIEW.md`, đặt trạng thái ở đây thành **"Chờ kiểm tra lại"**.
