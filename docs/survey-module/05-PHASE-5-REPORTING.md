# Phase 5 — Câu trả lời, thống kê và xuất dữ liệu

> **⚠ ĐÃ BỊ THAY THẾ (D27).** Mọi nội dung trong tài liệu này về `config/features.php`, `ThemeFeatures`, `RequireThemeFeature`, `cms.feature:khao-sat` hoặc "chức năng phụ thuộc theme" là **lỗi thời**. Cơ chế chính thức là **Feature Registry**:
> - Definition ở `Modules/CmsBackend/app/Features/Definitions/` (khảo sát: `SurveyFeature`, mã `survey`);
> - Trạng thái bật/tắt của bản triển khai nằm ở bảng `feature_settings`;
> - Route dùng middleware `cms.feature:<feature-key>` (ví dụ `cms.feature:survey`);
> - Theme chỉ phụ trách giao diện, không quyết định chức năng; dữ liệu nghiệp vụ không gắn theme.
> Xem `DECISIONS.md` mục D27.


## 1. Phạm vi đã thực hiện
- **Danh sách phản hồi**: mã lượt (rút gọn), người tham gia hoặc "Ẩn danh", thời gian bắt đầu/hoàn thành, thời lượng, trạng thái hoàn thành; tìm kiếm (tên/SĐT/email/mã), lọc trạng thái, ẩn danh/có thông tin, khoảng ngày; phân trang; xem chi tiết; xóa theo quyền.
- **Chi tiết lượt**: thông tin khảo sát, thông tin người tham gia, danh sách câu hỏi + câu trả lời (lựa chọn hiển thị theo nhãn; nhiều lựa chọn là danh sách), thời gian thực hiện, thông tin kỹ thuật tối thiểu.
- **Thống kê**: tổng lượt bắt đầu, lượt hoàn thành, tỷ lệ hoàn thành, thời gian hoàn thành trung bình, lượt theo ngày (bắt đầu/hoàn thành), thống kê từng lựa chọn (đếm + %), thang điểm (phân bố + trung bình), có/không, số câu trả lời văn bản.
- **Xuất Excel**: tổng hợp (3 trang: Tổng quan / Theo ngày / Theo câu hỏi) và chi tiết (một dòng/lượt hoàn thành, một cột/câu hỏi), áp dụng đúng bộ lọc đang chọn, chỉ người có `khaosat-export`.

## 2. Kiến trúc được chọn
`KhaoSatPhanHoiController` / `KhaoSatThongKeController` → `SurveyReportService` → `SurveyResponseDataProvider` → Model; xuất bằng `maatwebsite/excel` (đã có trong dự án): `SurveySummaryExport` (nhiều trang từ `SurveyArraySheet`), `SurveyDetailExport` (`FromGenerator`, đọc theo lô 500 lượt qua `lazyById` để không nạp toàn bộ vào bộ nhớ). Một hàm `normalizeFilters` duy nhất làm sạch bộ lọc (ngày hợp lệ, hoán đổi từ/đến nếu ngược, trạng thái trong danh sách cho phép) cho danh sách, thống kê và xuất — nên "xuất theo bộ lọc hiện tại" luôn trùng với điều đang xem. Không thêm thư viện biểu đồ: thống kê dùng bảng + thanh tiến độ (không cần build, đọc được bằng trình đọc màn hình).

Quy tắc số liệu: chỉ lượt **đã hoàn thành** và **chưa xóa** được tính vào thống kê lựa chọn; "lượt bắt đầu" gồm cả lượt đang làm dở (đã loại trùng do tiếp tục lượt cũ ở Phase 4). Câu hỏi/lựa chọn đã ngưng nhưng có câu trả lời vẫn xuất hiện (gắn nhãn "đã ngưng") để số liệu cũ không biến mất.

## 3. Danh sách file thêm mới
- `Modules/CmsBackend/app/Http/Controllers/KhaoSatPhanHoiController.php`, `KhaoSatThongKeController.php`
- `Modules/CmsBackend/app/Services/SurveyReportService.php`
- `Modules/CmsBackend/app/Exports/SurveyArraySheet.php`, `SurveySummaryExport.php`, `SurveyDetailExport.php`
- Views `.../screens/default/pages/khao-sat/`: `responses`, `statistics`, `_nav`, `_filters`
- `lang/en/survey.php`
- `tests/Feature/Survey/SurveySqliteTestCase.php`, `SurveyAdminReportTest.php`

## 4. Danh sách file chỉnh sửa
- `.../khao-sat/show.blade.php` (viết lại: chi tiết lượt thay cho chi tiết nghề nghiệp cũ)
- `Modules/CmsBackend/routes/web.php` (route phản hồi/thống kê/xuất)
- `Modules/CmsBackend/app/DataProviders/SurveyResponseDataProvider.php` (truy vấn đọc/thống kê)
- `Modules/CmsBackend/app/DataProviders/KhaoSatDataProvider.php` (bảng điều khiển vẫn mở được khi chưa chạy migration — xem rủi ro)
- `Modules/CmsFrontend/app/Models/KhaoSatUser.php` (route key `ksu_id`)
- `Modules/CmsBackend/app/Http/Controllers/KhaoSatController.php` (sửa `Toastr::error` không tồn tại → `warning`; phát hiện nhờ test)
- `lang/vi/admin.php` (nhóm `admin.survey_report`)
- `tests/Feature/Survey/SurveyPublicFlowTest.php` (dùng chung lớp nền).

## 5. Migration
Không thêm (dùng cột Phase 2).

## 6. Quan hệ dữ liệu
Đọc: `ks_khao_sat_users (ks_id) → ks_ket_qua (ksu_id, ch_id, trl_id) → ks_cau_hoi → ks_nhom`, `ks_tra_loi` (nhãn lựa chọn, kể cả đã xóa mềm). Xóa lượt = xóa mềm `ksu_deleted_at` (câu trả lời giữ nguyên trong DB nhưng không còn được tính/hiển thị).

## 7. Route mới
| Method | URI (`/cms/khao-sat/…`) | Tên | Quyền (ANY-of) |
|---|---|---|---|
| GET | `{survey}/phan-hoi` | `cms.khao-sat.responses` | `khaosat-response-view`, `khaosat-view` |
| GET | `luot/{participation}` | `cms.khao-sat.show` (tên cũ được dùng lại) | như trên |
| POST | `luot/{participation}/xoa` | `cms.khao-sat.responses.destroy` | `khaosat-response-delete` |
| GET | `{survey}/thong-ke` | `cms.khao-sat.statistics` | `khaosat-statistics-view` |
| GET | `{survey}/xuat?type=summary\|detail[&contact=1]&<bộ lọc>` | `cms.khao-sat.export` | `khaosat-export` |

Tất cả có `cms.auth` + `cms.feature:khao-sat`. URL chi tiết lượt cũ `/cms/khao-sat/{ksu_id}` không còn (tên route giữ nguyên).

## 8. Permission
Dùng các quyền Phase 3. `khaosat-view` (cũ) vẫn xem danh sách/chi tiết phản hồi nhưng **không** thống kê, xuất hay xóa — đã có test.

## 9. Giữ lại từ code cũ
Layout/phân trang admin, `ModuleUrl`, thông số `maatwebsite/excel`, mẫu `Excel::download` của module bài viết; `KhaoSatService/DataProvider` cũ cho biểu đồ nghề nghiệp ở bảng điều khiển.

## 10. Không sử dụng và lý do
- Không dùng `ksu_ket_qua`/`school_*` (nghề nghiệp).
- Không có biểu đồ JS (cần build/thư viện) — dùng bảng + thanh tiến độ.
- Không xuất IP băm, dấu vết trình duyệt (`ksu_dau_vet`), user-agent trong **bất kỳ** file xuất nào; chi tiết lượt không hiển thị IP/dấu vết (chỉ user-agent rút gọn 120 ký tự để biết thiết bị).

## 11. Xuất dữ liệu: dữ liệu nhạy cảm
- Tổng hợp: không có dữ liệu cá nhân.
- Chi tiết: mặc định chỉ mã lượt (UUID), cờ ẩn danh, thời gian, câu trả lời. Họ tên/SĐT/email chỉ khi bật tường minh `contact=1` (nút riêng, nhãn rõ), và vẫn cần `khaosat-export`. Mỗi lần xuất được ghi log (`survey.export`: người dùng, khảo sát, loại, có kèm liên hệ, bộ lọc).
- Chống "CSV/Excel injection": ô chuỗi bắt đầu bằng `= + - @`, tab hoặc CR được thêm dấu `'` đứng đầu (test xác nhận với `=HYPERLINK(...)`).
- Chỉ lượt đã hoàn thành được xuất chi tiết.

## 12. Rủi ro còn lại
- **Thứ tự triển khai**: code mới dùng cột mới (`ksu_deleted_at`…). Bảng điều khiển cũ đã được bảo vệ (bỏ scope xóa mềm khi cột chưa có) và đã xác nhận mở được trên DB hiện tại (bài test sẵn có `test_administrator_can_open_existing_management_pages` đạt), nhưng các màn hình khảo sát mới chỉ chạy được **sau khi người dùng chạy migration**.
- Xuất chi tiết khảo sát rất lớn (hàng chục nghìn lượt) chạy đồng bộ trong request; đã đọc theo lô nhưng file vẫn dựng trong bộ nhớ của thư viện Excel. Cần chuyển sang hàng đợi nếu dữ liệu thực tế lớn.
- Thống kê tính trực tiếp từ `ks_ket_qua` (không cache); có index `(ks_id,ch_id)` và `(ksu_id)` nhưng chưa đo trên dữ liệu thật.
- Câu trả lời văn bản không được liệt kê trong màn hình thống kê (chỉ số lượng) — xem ở chi tiết lượt hoặc file xuất chi tiết.
- Chưa kiểm tra giao diện admin bằng trình duyệt thật (kiểm tra bằng render Blade + HTTP trong test).
- `lang/en/admin.php` chưa có nhóm `survey`/`survey_report` (giao diện quản trị mặc định tiếng Việt; locale `en` sẽ hiện khóa).

## 13. Việc chưa hoàn thành
Không có yêu cầu Phase 5 nào bỏ sót. Ngoài phạm vi (cố ý): hàng đợi xuất, biểu đồ đồ họa, bản dịch tiếng Anh cho giao diện quản trị.

## 14. Hướng dẫn kiểm tra
- `DB_CONNECTION=sqlite DB_DATABASE=:memory: php -d extension=pdo_sqlite -d extension=sqlite3 -d auto_prepend_file=docs/survey-module/verification/prepend.php vendor/bin/phpunit tests/Feature/Survey` → **26 test, 209 assertion, OK** (số liệu thống kê, từng bộ lọc, xóa mềm, file Excel được đọc lại bằng PhpSpreadsheet, không lộ dữ liệu cá nhân/kỹ thuật, ma trận quyền theo từng route, cổng theme 404 trên mọi route quản trị, render các trang admin).
- Không có `DB_CONNECTION=sqlite` thì các test khảo sát tự `skip` (không chạm DB thật).
- Các test cũ `PhaseOneIsolationTest`, `ScreenTheme*`, `Sbut*`, `BanDeAn*` có nhiều test **đã lỗi sẵn** không liên quan khảo sát (302/403, đếm số theme, tiền tố `/cms`); không có git để so sánh nên chưa chứng minh chúng lỗi trước thay đổi này — xem FINAL-STATUS. Riêng `test_administrator_can_open_existing_management_pages` đạt.

## 15. Xác nhận
Không build, không migrate DB thật, không seed, không deploy, không xóa dữ liệu. Kiểm thử chỉ trên SQLite bộ nhớ (cộng vài lần chạy lệnh đọc-chỉ trên DB thật ở Phase 1 và các test cũ có sẵn của dự án, xem FINAL-STATUS).
