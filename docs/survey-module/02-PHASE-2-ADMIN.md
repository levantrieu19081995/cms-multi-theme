# Phase 2 — Hoàn thiện quản trị khảo sát

> **⚠ ĐÃ BỊ THAY THẾ (D27).** Mọi nội dung trong tài liệu này về `config/features.php`, `ThemeFeatures`, `RequireThemeFeature`, `cms.feature:khao-sat` hoặc "chức năng phụ thuộc theme" là **lỗi thời**. Cơ chế chính thức là **Feature Registry**:
> - Definition ở `Modules/CmsBackend/app/Features/Definitions/` (khảo sát: `SurveyFeature`, mã `survey`);
> - Trạng thái bật/tắt của bản triển khai nằm ở bảng `feature_settings`;
> - Route dùng middleware `cms.feature:<feature-key>` (ví dụ `cms.feature:survey`);
> - Theme chỉ phụ trách giao diện, không quyết định chức năng; dữ liệu nghiệp vụ không gắn theme.
> Xem `DECISIONS.md` mục D27.


## 1. Phạm vi đã thực hiện
- Migration cộng thêm theo bản ánh xạ Phase 1.
- Danh sách khảo sát: tìm kiếm (tên/mã), lọc trạng thái (+ "Đã xóa"), tạo mới, chỉnh sửa, nhân bản, xem trước, xóa an toàn/khôi phục, số câu hỏi, số lượt (hoàn thành/tổng), thời gian bắt đầu–kết thúc.
- Thông tin khảo sát: tên, mã/slug, mô tả ngắn, giới thiệu, loại, trạng thái, bắt đầu/kết thúc, ẩn danh, yêu cầu thông tin cá nhân, số lần gửi, nội dung hoàn thành, ghi chú nội bộ.
- Trình dựng câu hỏi: phần/nhóm, một lựa chọn, nhiều lựa chọn, văn bản ngắn/dài, thang điểm, có/không, bắt buộc, bật/tắt, nhân bản câu hỏi, kéo thả phần/câu hỏi (kể cả chuyển giữa các phần)/lựa chọn.
- Vòng đời trạng thái và quy tắc bảo vệ dữ liệu (mục 4).

## 2. Kiến trúc được chọn
`KhaoSatController` → `SurveyInfoRequest` / `SurveyStructureRequest` → `SurveyService` → `SurveyDataProvider` → Model (`Modules\CmsFrontend\Models\*`). Controller/Service nhận phụ thuộc qua DI (không `new`). Trình dựng là JS thuần (`public/assets/js/survey/builder.js`) + SortableJS có sẵn, cấu hình/nhãn/loại câu hỏi được Blade sinh ra (JSON), không viết cứng URL hay ID; gửi cấu trúc dưới dạng JSON trong một trường (`structure`) rồi **Service kiểm tra lại toàn bộ** (không tin trình duyệt). Định danh phần tử khi lưu = ID thật (`id`), phần tử mới `id = null`; mã công khai UUID do server cấp.

## 3. Danh sách file thêm mới
- `database/migrations/2026_10_31_000001_extend_ks_khao_sat_table.php`
- `database/migrations/2026_10_31_000002_create_ks_nhom_table.php`
- `database/migrations/2026_10_31_000003_extend_ks_cau_hoi_and_ks_tra_loi_tables.php`
- `database/migrations/2026_10_31_000004_extend_ks_participation_and_answer_tables.php`
- `Modules/CmsBackend/app/Enums/SurveyStatus.php`, `SurveyQuestionType.php`
- `Modules/CmsBackend/app/Exceptions/SurveyRuleException.php`
- `Modules/CmsBackend/app/DataProviders/SurveyDataProvider.php`
- `Modules/CmsBackend/app/Services/SurveyService.php`, `SurveyFormService.php`
- `Modules/CmsBackend/app/Http/Requests/SurveyInfoRequest.php`, `SurveyStructureRequest.php`
- `Modules/CmsFrontend/app/Models/KhaoSatNhom.php`
- `Modules/CmsBackend/resources/views/screens/default/pages/khao-sat/edit.blade.php`
- `public/assets/js/survey/builder.js`
- `docs/survey-module/verification/verify_phase2.php`, `prepend.php`

## 4. Danh sách file chỉnh sửa
- Model `KhaoSat`, `CauHoi`, `TraLoi`, `KetQua`, `KhaoSatUser` (cột timestamp tùy biến, soft delete, fillable, quan hệ).
- `KhaoSatController` (viết lại), view `khao-sat/index.blade.php` (viết lại).
- `Modules/CmsBackend/routes/web.php`, `Modules/CmsFrontend/routes/web.php` (gỡ route khảo sát trùng URI).
- `Modules/CmsBackend/app/DataProviders/KhaoSatDataProvider.php` (thêm bộ lọc `has_result`), `CmsBackendController.php` (dashboard dùng bộ lọc đó — D12).
- `lang/vi/admin.php` (nhóm `admin.survey`).
- `PermissionsEnum.php`, `RouteServiceProvider.php`, `PermissionAvailability.php`: thuộc Phase 3, ghi ở đó.

## 5. Migration đã viết (chưa chạy)
Tất cả chỉ cộng thêm, có guard `hasTable/hasColumn`, `down()` cố ý rỗng (không xóa dữ liệu). Không `dropIfExists`, không `->change()`, không FK. Backfill chỉ ghi vào cột **mới**: `ks_status='open'` cho khảo sát cũ có `ks_trang_thai=1`; cấp UUID cho bản ghi cũ chưa có mã. Đã kiểm chứng chạy 2 lần liên tiếp trên SQLite bộ nhớ (không phải DB thật).

## 6. Quan hệ dữ liệu
Như Phase 1 mục 8. `KhaoSat` hasMany `nhom` / `cauHoi` / `luotThamGia`; `KhaoSatNhom` hasMany `cauHoi`; `CauHoi` hasMany `traLoi` / `ketQua`; `KetQua` belongsTo `cauHoi` (withTrashed) / `luot` / `luaChon` (withTrashed).

## 7. Route mới/thay đổi
Gốc `/cms/khao-sat`, mọi route có `cms.auth` + `cms.feature:khao-sat` + quyền riêng:

| Method | URI | Tên | Quyền (ANY-of) |
|---|---|---|---|
| GET | `/` | `cms.khao-sat` (tên cũ giữ nguyên) | view, create, update |
| GET | `tao-moi` | `cms.khao-sat.create` | create |
| POST | `/` | `cms.khao-sat.store` | create |
| GET | `{survey}/chinh-sua` | `cms.khao-sat.edit` | view, update |
| POST | `{survey}/cap-nhat` | `cms.khao-sat.update` | update |
| POST | `{survey}/cau-hoi` | `cms.khao-sat.structure` | update |
| POST | `{survey}/trang-thai` | `cms.khao-sat.status` | publish |
| POST | `{survey}/nhan-ban` | `cms.khao-sat.duplicate` | create |
| GET | `{survey}/xem-truoc` | `cms.khao-sat.preview` | view, update |
| POST | `{survey}/xoa` | `cms.khao-sat.destroy` | delete |
| POST | `{survey}/khoi-phuc` | `cms.khao-sat.restore` | delete |

Đã gỡ: `cms.dashboard.khao-sat` (trùng) và `GET /cms/khao-sat/{ksu_id}`. Tên `cms.khao-sat.show` được dùng lại cho chi tiết lượt tham gia ở Phase 5 (URL mới `/cms/khao-sat/luot/{ksu}`). Dùng POST thay PUT/DELETE vì edge production từ chối method-override (như các module khác).

## 8. Permission mới/thay đổi
Xem Phase 3.

## 9. Quy tắc bảo vệ dữ liệu (thực thi trong `SurveyService`/`SurveyDataProvider`, không chỉ ở UI)
1. Nháp: sửa toàn bộ. Đang mở/Tạm dừng: chỉ sửa thông tin chung; **mã (slug) bị khóa từ khi rời Nháp** để liên kết công khai không vỡ; cấu trúc bị khóa (`structure_locked`). Kết thúc: chỉ xem/thống kê (`ended_read_only`).
2. Thay đổi lớn → **Nhân bản** (tạo Nháp mới, mã mới, UUID mới, không kèm lượt/câu trả lời).
3. Chuyển trạng thái chỉ đi tới: Nháp→Mở; Mở⇄Tạm dừng; Mở/Tạm dừng→Kết thúc. Không về Nháp, không mở lại sau Kết thúc. Phát hành yêu cầu ≥1 câu hỏi bật, mã, và thời gian kết thúc chưa qua.
4. Gỡ câu hỏi/lựa chọn đã có câu trả lời → **xóa mềm** (giữ nguyên, kết quả cũ vẫn đọc được); chưa có câu trả lời mới xóa hẳn. Câu hỏi đã xóa mềm không "sống lại" bằng ID cũ.
5. Xóa khảo sát = xóa mềm (có khôi phục); không xóa khảo sát đang mở; không có xóa vật lý khảo sát hay lượt tham gia.
6. HTML giới thiệu bị loại `script/style/iframe/object/embed`, thuộc tính `on*`, URL `javascript:` trước khi lưu.
7. Giới hạn: 30 phần, 300 câu hỏi, 50 lựa chọn/câu; thang điểm 0–10.

## 10. Những phần giữ lại từ code cũ
Tên bảng/cột, 5 model, route name `cms.khao-sat`, `KhaoSatService/DataProvider` (dashboard), view layout `layoutMaster` và phân trang.

## 11. Những phần không sử dụng và lý do
- View `show.blade.php` cũ (chi tiết người làm nghề nghiệp): được viết lại ở Phase 5.
- Cột `ksu_ket_qua`, `school_*`: giữ nguyên, không dùng.
- Câu hỏi cũ không thuộc nhóm (`nhom_id` null): hiển thị công khai như một phần "không tiêu đề" nhưng **không** nằm trong trình dựng/nhân bản (bảng hiện rỗng).

## 12. Rủi ro còn lại
- Chưa chạy migration trên DB thật; kiểm chứng bằng SQLite có thể khác MySQL ở kiểu `json`/index (không dùng tính năng riêng của driver nào).
- Trình dựng chưa được thử trên trình duyệt (không có bản chạy giao diện trong môi trường này); logic JS chỉ kiểm tra cú pháp. Cần người kiểm tra thao tác kéo thả thực tế.
- `ch_trang_thai` mặc định `false` ở bảng cũ (xem Phase 1).
- Mọi `artisan` bootstrap ở dự án này tăng bộ đếm truy cập (AppServiceProvider, hành vi có sẵn). Các lệnh đọc schema ở Phase 1 có thể đã tăng bộ đếm `visits`; các kiểm chứng sau đó dùng `prepend.php` để tránh.

## 13. Việc chưa hoàn thành
Không có trong phạm vi Phase 2. Liên kết sang phản hồi/thống kê (Phase 5) chỉ hiện khi route tồn tại.

## 14. Hướng dẫn kiểm tra
- Đọc `SurveyService`/`SurveyDataProvider::persistStructure`.
- Chạy kiểm chứng (không đụng DB thật): `DB_CONNECTION=sqlite DB_DATABASE=:memory: php -d extension=pdo_sqlite -d extension=sqlite3 -d auto_prepend_file=docs/survey-module/verification/prepend.php artisan tinker --execute="require 'docs/survey-module/verification/verify_phase2.php';"` → toàn bộ dòng `PASS` (30 kiểm tra).
- `php artisan route:list --name=khao-sat`.

## 15. Xác nhận
Không build, không migrate (DB thật), không seed, không deploy. Chỉ chạy PHP lint, `route:list`, và script kiểm chứng trên SQLite bộ nhớ.
