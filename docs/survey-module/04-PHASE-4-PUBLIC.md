# Phase 4 — Trang công khai làm khảo sát

> **⚠ ĐÃ BỊ THAY THẾ (D27).** Mọi nội dung trong tài liệu này về `config/features.php`, `ThemeFeatures`, `RequireThemeFeature`, `cms.feature:khao-sat` hoặc "chức năng phụ thuộc theme" là **lỗi thời**. Cơ chế chính thức là **Feature Registry**:
> - Definition ở `Modules/CmsBackend/app/Features/Definitions/` (khảo sát: `SurveyFeature`, mã `survey`);
> - Trạng thái bật/tắt của bản triển khai nằm ở bảng `feature_settings`;
> - Route dùng middleware `cms.feature:<feature-key>` (ví dụ `cms.feature:survey`);
> - Theme chỉ phụ trách giao diện, không quyết định chức năng; dữ liệu nghiệp vụ không gắn theme.
> Xem `DECISIONS.md` mục D27.


## 1. Phạm vi đã thực hiện
Luồng: mở khảo sát → kiểm tra trạng thái/thời gian → giới thiệu → (đồng ý + thông tin cá nhân nếu khảo sát yêu cầu) → trả lời theo phần → kiểm tra câu bắt buộc → gửi → lưu → lời cảm ơn. Có trạng thái thân thiện cho: không tồn tại/nháp, tạm dừng, chưa mở, đã kết thúc, đã gửi đủ số lần.

## 2. Kiến trúc được chọn
`KhaoSatPublicController` (dùng chung) → `SurveyStartRequest` / `SurveySubmitRequest` → `SurveyPublicService` → `SurveyResponseDataProvider` / `SurveyDataProvider` / `SurveyFormService` → Model. Tất cả phụ thuộc qua DI (kể cả `rules()` của FormRequest). Blade chỉ nhận dữ liệu đã dựng (`$form`, `$survey`); không truy vấn DB trong Blade; URL do Laravel sinh (`route()`), JS không chứa URL.

Mã định danh công khai: khảo sát = `ks_ma` (slug); câu hỏi/lựa chọn = UUID (`ch_ma`/`trl_ma`); lượt tham gia = UUID (`ksu_ma`) lưu trong **session** (không nằm trong URL). Trường form: `answers[<ch_ma>]` / `answers[<ch_ma>][]`, giá trị lựa chọn = `trl_ma`. Không có ID tự tăng hay chỉ số 1..N ở HTML/JS.

## 3. Danh sách file thêm mới
- `Modules/CmsFrontend/app/Http/Controllers/KhaoSatPublicController.php`
- `Modules/CmsFrontend/app/Http/Requests/SurveyStartRequest.php`, `SurveySubmitRequest.php`
- `Modules/CmsFrontend/app/Services/SurveyPublicService.php`
- `Modules/CmsBackend/app/DataProviders/SurveyResponseDataProvider.php`
- `lang/vi/survey.php`
- Views `Modules/CmsFrontend/resources/views/screens/default/pages/khao-sat/`: `layout`, `theme-vars`, `gioi-thieu`, `lam-bai`, `cam-on`, `khong-kha-dung`
- Ghi đè theme (chỉ trình bày): `screens/nam-binh-duong/pages/khao-sat/theme-vars.blade.php`, `screens/ban-de-an/pages/khao-sat/theme-vars.blade.php`
- `tests/Feature/Survey/SurveyPublicFlowTest.php`

## 4. Danh sách file chỉnh sửa
- `Modules/CmsFrontend/routes/public.php` (nhóm route khảo sát)
- `Modules/CmsFrontend/app/Providers/RouteServiceProvider.php` (rate limiter `survey-read`, `survey-write`)
- `Modules/CmsBackend/app/Http/Controllers/KhaoSatController.php` (action `preview` dùng cùng view công khai).

## 5. Migration
Không thêm (dùng cột của Phase 2).

## 6. Quan hệ dữ liệu
Mỗi lượt = 1 dòng `ks_khao_sat_users` (`ks_id`, `ksu_ma`, `ksu_trang_thai` in_progress→completed, `ksu_created_at` = lúc bắt đầu, `ksu_hoan_thanh_at`, `ksu_thoi_luong` giây). Mỗi giá trị trả lời = 1 dòng `ks_ket_qua` (`ch_id`, `trl_id` null với văn bản, `kskq_trl_value`, `kskq_van_ban` đầy đủ). Nhiều lựa chọn → nhiều dòng. Ẩn danh: `ksu_an_danh=1`, họ tên/SĐT = `''`, email null.

## 7. Route công khai (mặc định ngôn ngữ; tiền tố gốc miền, `CMS_PUBLIC_ROUTE_PREFIX`)
Middleware chung: `web` (CSRF, session) + `cms.feature:khao-sat` (404 khi theme tắt).

| Method | URI | Tên | Giới hạn |
|---|---|---|---|
| GET | `/khao-sat/{slug}` | `cms.frontend.khaosat.show` | `survey-read` 60/phút/IP |
| POST | `/khao-sat/{slug}/bat-dau` | `…start` | `survey-write` |
| GET | `/khao-sat/{slug}/lam-bai` | `…form` | `survey-read` |
| POST | `/khao-sat/{slug}/gui` | `…submit` | `survey-write` |
| GET | `/khao-sat/{slug}/cam-on` | `…thanks` | `survey-read` |

`survey-write`: 10/phút theo (IP + khảo sát) và 60/giờ theo IP. Xem trước (quản trị): `GET /cms/khao-sat/{id}/xem-truoc` render cùng view, `preview=true`, không ghi dữ liệu, không phụ thuộc trạng thái/thời gian.

## 8. Permission
Không có (trang công khai). Quản trị xem trước cần `khaosat-view`/`khaosat-update`.

## 9. Cơ chế fallback theme
Mọi view gọi qua `cmsfrontend::screens.pages.khao-sat.*`; `ScreenViewFinder`/`ScreenTheme::path` tìm `screens/<theme hiện hành>/…` rồi `screens/default/…`. Mọi view đều `@extends` layout mặc định, layout `@include` `theme-vars` — theme chỉ cần ghi đè `theme-vars.blade.php` (màu, font, bo góc, kiểu tiến độ qua `<meta name="sv-progress" content="bar|steps">`) hoặc ghi đè cả `layout.blade.php`/từng view. Theme không có file riêng → dùng bản `default`, không lỗi (test: `theme-vars` của `default` được dùng khi bật Khảo sát cho theme `default`). Nghiệp vụ, controller, service, route **không** phụ thuộc theme. Không cần Vite entry: CSS/JS nằm trong layout/view, nên không cần build.

## 10. Chống gửi trùng và validation
Validation (2 lớp, quy tắc dựng từ chính cấu trúc khảo sát):
- `SurveyStartRequest`: khảo sát không yêu cầu thông tin → không thu thập gì (dữ liệu thừa bị bỏ); yêu cầu → `dong_y` (đồng ý), `ho_ten`, và `so_dien_thoai` hoặc `email`; nếu khảo sát cho phép ẩn danh và chọn `an_danh` thì bỏ qua phần này. **Tham số `an_danh` bị bỏ qua khi khảo sát cấm ẩn danh.**
- `SurveySubmitRequest`: theo từng câu — bắt buộc; một lựa chọn/Có-Không/nhiều lựa chọn phải thuộc **đúng** tập mã của câu hỏi đó (đáp án của câu khác bị loại); thang điểm nguyên trong khoảng cấu hình; văn bản ngắn ≤255, dài ≤5000. Câu hỏi/lựa chọn đã tắt không được chấp nhận. `SurveyPublicService::buildAnswerRows` kiểm tra lại độc lập (bắt buộc thiếu, mã lạ).

Chống gửi trùng/lặp:
1. **Idempotent theo lượt**: `submit` chạy trong transaction, khóa dòng lượt (`lockForUpdate`); lượt đã `completed` → trả về trang cảm ơn, không ghi thêm (nhấp đúp, F5, replay).
2. **Giới hạn số lần theo trình duyệt** (`ks_so_lan_gui`, 0 = không giới hạn): dấu vết = HMAC-SHA256(cookie ngẫu nhiên `ks_client` 40 ký tự, APP_KEY), lưu ở `ksu_dau_vet`; vượt giới hạn → lỗi thân thiện, không tạo lượt. Không lưu cookie gốc hay IP gốc (chỉ HMAC; kiểm thử xác nhận).
3. **Gắn lượt với trình duyệt**: mã lượt chỉ có trong session và phải khớp dấu vết; mã bị lấy sang trình duyệt khác bị từ chối.
4. **Bắt đầu lặp lại** dùng lại lượt đang làm dở (24h) nên không làm phồng số "lượt bắt đầu".
5. Rate limit (mục 7), CSRF (nhóm `web`), ô bẫy bot `sv_website`, nút gửi bị vô hiệu sau khi bấm.
6. Đóng/tạm dừng giữa lúc đang làm: `submit` kiểm tra lại trạng thái/thời gian → không lưu.

Không lộ thông tin quản trị: không có điểm, ghi chú nội bộ, trạng thái nội bộ, ID tự tăng hay đường dẫn quản trị trong trang công khai; khảo sát nháp/đã xóa trả 404 như không tồn tại. Toàn bộ nội dung nhập được Blade escape; riêng "nội dung giới thiệu" do quản trị nhập là HTML đã lọc (loại script/style/iframe/`on*`/`javascript:`) khi lưu.

## 11. Giữ lại từ code cũ
Bảng/model `ks_*`, `KhaoSatUser` (làm lượt tham gia), mẫu FormRequest/Controller của module Liên hệ/Hỏi đáp (bố cục), `ScreenTheme` để chọn view.

## 12. Không sử dụng và lý do
Layout công khai của theme (cần Vite build) — trang khảo sát dùng layout độc lập tự chứa để không cần build và không phụ thuộc asset theme. `KhaoSatService::saveSurvey/createUser` cũ (nghề nghiệp) — không dùng.

## 13. Rủi ro còn lại
- Chống gửi lặp theo trình duyệt có thể bị vượt bằng xóa cookie/ẩn danh (không xác thực người dùng) — chấp nhận được với khảo sát công khai; rate limit giảm thiểu lạm dụng. Muốn chặt hơn phải yêu cầu định danh.
- Chưa kiểm tra giao diện trên trình duyệt thật (kéo thả/tiến độ/di động); mới kiểm tra HTML sinh ra bằng test và cú pháp JS bằng test render. Không có header CSP cho trang này.
- Layout khảo sát không dùng header/footer của theme, nên chưa "giống" 100% website; có thể ghi đè `layout.blade.php` theo theme.
- Khảo sát cũ chưa có `ks_ma` (null) sẽ không truy cập công khai được cho đến khi gán mã (bảng hiện rỗng).
- Giới hạn trình duyệt dùng chung IP không ảnh hưởng (giới hạn theo cookie), nhưng rate limit theo IP có thể chặn nhiều người cùng NAT khi gửi dồn (10 lượt/phút/khảo sát).

## 14. Việc chưa hoàn thành
Không có trong phạm vi; xem rủi ro. Chưa có bản dịch tiếng Anh cho `lang/en/survey.php` (locale `vi` là mặc định; locale `en` rơi về khóa trống).

## 15. Hướng dẫn kiểm tra
`DB_CONNECTION=sqlite DB_DATABASE=:memory: php -d extension=pdo_sqlite -d extension=sqlite3 -d auto_prepend_file=docs/survey-module/verification/prepend.php vendor/bin/phpunit tests/Feature/Survey/SurveyPublicFlowTest.php` → **16 test, 106 assertion, OK** (tự bỏ qua nếu DB không phải SQLite :memory:, nên không chạm DB thật). Bao gồm: cổng theme (404), trạng thái/thời gian, nháp ẩn, luồng đầy đủ + replay, giới hạn/đa trình duyệt, mã lượt trái trình duyệt, đóng giữa chừng, honeypot, 3 chế độ định danh, không lưu IP/cookie gốc, theme override + fallback, HTML được lọc/escape, xem trước.

## 16. Xác nhận
Không build (không đụng Vite), không migrate DB thật, không seed, không deploy. Kiểm thử chạy trên SQLite bộ nhớ; migration chạy trên đó bằng chính các file migration mới.
