# Nhật ký sửa lỗi từ review độc lập

> **⚠ ĐÃ BỊ THAY THẾ (D27).** Mọi nội dung trong tài liệu này về `config/features.php`, `ThemeFeatures`, `RequireThemeFeature`, `cms.feature:khao-sat` hoặc "chức năng phụ thuộc theme" là **lỗi thời**. Cơ chế chính thức là **Feature Registry**:
> - Definition ở `Modules/CmsBackend/app/Features/Definitions/` (khảo sát: `SurveyFeature`, mã `survey`);
> - Trạng thái bật/tắt của bản triển khai nằm ở bảng `feature_settings`;
> - Route dùng middleware `cms.feature:<feature-key>` (ví dụ `cms.feature:survey`);
> - Theme chỉ phụ trách giao diện, không quyết định chức năng; dữ liệu nghiệp vụ không gắn theme.
> Xem `DECISIONS.md` mục D27.


Nguồn: `CODEX-REVIEW.md` (ngày rà soát 2026-10-30). **Không sửa file đó** và không tự đổi trạng thái trong đó (đúng chỉ dẫn ban đầu của người dùng). Lưu ý: chính file review có quy tắc "agent đổi `OPEN` → `FIXED BY AGENT`"; hai chỉ dẫn mâu thuẫn nên tôi chọn chỉ dẫn của người dùng và ghi trạng thái ở đây. Nếu muốn đánh dấu trong file review, hãy yêu cầu rõ.

Test: `tests/Feature/Survey` = **81 test, 950 assertion, OK** (SQLite `:memory:`; lệnh chạy ở `FINAL-STATUS.md`).

## Trạng thái từng mục

| Mã | Mức | Trạng thái (theo agent) |
|---|---|---|
| KS-001 | P1 | Đã sửa — chờ reviewer kiểm tra lại |
| KS-002 | P1 | Đã sửa — chờ reviewer kiểm tra lại |
| KS-003 | P1 | Đã sửa — chờ reviewer kiểm tra lại |
| KS-004 | P2 | Đã sửa ở mức service — **cần kiểm chứng đồng thời trên MySQL thật (KS-010)** |
| KS-005 | P2 | **Chưa sửa — cần quyết định của chủ dự án** |
| KS-006 | P2 | Đã sửa — chờ reviewer kiểm tra lại |
| KS-007 | P2 | **Chưa sửa — cần quyết định nghiệp vụ** |
| KS-008 | P2 | Đã sửa — chờ reviewer kiểm tra lại |
| KS-009 | P3 | Đã sửa (nhật ký thao tác) — chờ reviewer kiểm tra lại |
| KS-010 | P3 | **Chưa làm** (cần bản sao DB cùng engine/phiên bản production và trình duyệt) |
| KS-011 | P3 | Giữ nguyên (hạn chế đã biết, đã ghi trong Phase 4 mục 13; không phải lỗi mã) |

## KS-001 — Stored XSS ở nội dung giới thiệu
- **Nguyên nhân:** bộ lọc là 3 regex tự viết, rồi in `{!! !!}`.
- **File sửa/thêm:** `Modules/CmsBackend/app/Support/SurveyHtmlSanitizer.php` (mới, HTMLPurifier allow-list: p, br, strong/b, em/i, u, s, ul/ol/li, h2–h4, blockquote, hr, span, a[href|title|target], img[src|alt|width|height]; scheme chỉ http/https/mailto/tel; `rel=noopener noreferrer`), `SurveyService` (bỏ `cleanHtml` regex, dùng sanitizer), `SurveyPublicService::introHtml()` (lọc lại **lúc hiển thị**, cache theo nội dung), `KhaoSatPublicController`, view `gioi-thieu.blade.php` (in `$introHtml`).
- **Ảnh hưởng:** HTML đã lưu bằng bộ lọc cũ cũng được lọc khi hiển thị. HTMLPurifier là phụ thuộc gián tiếp có sẵn trong `vendor` (chưa khai báo trực tiếp trong `composer.json` — nên thêm `ezyang/htmlpurifier` vào `require` khi bạn đồng ý, tôi không sửa `composer.json`/`composer.lock`). Cache định nghĩa ở `storage/framework/cache/htmlpurifier`.
- **Test:** `SurveyReviewRegressionTest::test_ks001_*` — 18 payload (entity-obfuscated, tab trong scheme, `data:`, `vbscript:`, img onerror, svg onload, thẻ không đóng, script tách đôi, iframe/object/embed/style/form/meta/math, style attribute, handler trên thẻ hợp lệ) × 2 đường (lưu qua service và dữ liệu bẩn ghi thẳng DB); kiểm tra DOM: không còn script/style/iframe/object/embed/form/svg/math/meta/input, không thuộc tính `on*`/`style`, URL có scheme chỉ http/https/mailto/tel; định dạng hợp lệ vẫn còn.
- **Reviewer kiểm tra lại:** cấu hình allow-list có đủ cho nhu cầu soạn thảo thực tế (ví dụ có cần bảng/ảnh nội bộ không); `img` cho phép URL http/https bên ngoài.

## KS-002 — Quyền độc lập thấy menu nhưng 403
- **Nguyên nhân:** route danh sách chỉ nhận view/create/update.
- **File sửa:** `Modules/CmsBackend/routes/web.php` (danh sách nhận cả 9 quyền khảo sát), `project-verticalMenu.json` (thêm `delete`, `response-delete`, `export` vào `roler`), `khao-sat/index.blade.php` (nút sửa/xem trước chỉ khi có view/update; nút chuyển trạng thái khi có publish; liên kết xuất tổng hợp khi có export; xóa khi có delete; phản hồi/thống kê theo quyền riêng).
- **Ảnh hưởng:** chỉ mở điều hướng/đọc danh sách tối thiểu; mọi route ghi giữ quyền riêng (test xác nhận `response-view` vẫn 403 khi sửa/xóa/phát hành).
- **Test:** `test_ks002_each_single_permission_reaches_the_list_and_sees_only_its_own_actions` (response-view, statistics-view, export, delete, response-delete, publish; nội dung hiển thị đúng/không lộ hành động ngoài quyền; quyền không liên quan vẫn 403) và `test_ks002_every_menu_role_can_reach_the_list_route` (mọi `roler` của mục menu đều vào được route).
- **Lưu ý:** người chỉ có `export` hoặc `delete` thấy danh sách nhưng chỉ có đúng nút của mình; muốn xuất chi tiết/kèm liên hệ vẫn cần vào trang phản hồi (cần `response-view`) — xuất tổng hợp có ngay ở danh sách.

## KS-003 — Phát hành khảo sát không có câu hỏi hiển thị
- **File sửa:** `SurveyService::assertPublishable` (dùng `SurveyFormService::schema()` — cùng schema với trang công khai; câu hỏi lựa chọn đang bật phải có ≥2 lựa chọn đang bật), lang VI/EN (`publish_needs_question`, `publish_choice_options`).
- **Test:** tất cả lựa chọn tắt; chỉ một lựa chọn bật; câu hỏi bị tắt; một câu hỏng làm chặn dù câu khác ổn; khảo sát hợp lệ phát hành được và có `question_count = 1`; thông báo VI.
- **Reviewer kiểm tra lại:** lưu cấu trúc (draft) vẫn cho lưu câu hỏi chưa đủ lựa chọn bật — chỉ chặn ở bước phát hành (cố ý để không cản việc soạn).

## KS-004 — Race condition giới hạn số lần gửi
- **File sửa:** `SurveyResponseDataProvider::lockSurvey()` (`SELECT … FOR UPDATE` dòng khảo sát), `SurveyPublicService::start/submit` (khóa khảo sát trong transaction **trước** khi đọc lượt, kiểm tra mở/giới hạn và ghi; thứ tự khóa cố định khảo sát → lượt).
- **Ảnh hưởng:** mọi bắt đầu/gửi của **cùng một khảo sát** được tuần tự hóa (đủ cho lưu lượng khảo sát; nếu khảo sát cực lớn có thể xem xét khóa theo fingerprint).
- **Test:** `test_ks004_*` chứng minh thứ tự và việc khóa nằm trong transaction (`lock → find → count → insert`). **Hạn chế trung thực:** SQLite bỏ qua `FOR UPDATE` nên test **không** tạo được hai request song song thật; cần kiểm chứng đồng thời trên MySQL (KS-010).

## KS-005 / KS-007 — chờ quyết định
- **KS-005:** hiện cấu hình theme→chức năng là **một nguồn duy nhất** `config/features.php` (không có nguồn thứ hai ở Developer Settings; Developer Settings chỉ hiển thị cờ "Tắt theo theme"). Đây là thiết kế "cấu hình kỹ thuật cố định theo source" (DECISIONS D6/D13). Nếu muốn Developer Settings quản lý công tắc, cần quyết định lưu ở manifest hiện có (chữ ký HMAC, gắn project) hay option DB — tôi chưa làm để tránh tạo hai nguồn sự thật.
- **KS-007:** chưa khóa các trường thu thập (`ẩn danh`, `yêu cầu thông tin`, `số lần gửi`, `loại`, `ngày bắt đầu`) sau khi Mở/đã có phản hồi. Đề xuất: sau khi rời Nháp khóa bốn trường đầu và ngày bắt đầu; cho sửa tên, mô tả, giới thiệu, ngày kết thúc, lời cảm ơn, ghi chú. Cần bạn xác nhận trước khi sửa.

## KS-006 — Bản dịch quản trị tiếng Anh
- **File sửa:** `lang/en/admin.php` (nhóm `survey`, `survey_report`, `developer.theme_blocked*`; `menu.quan_ly_khao_sat` đã có).
- **Test:** `SurveyI18nTest` — cây khóa VI/EN trùng khớp (admin và public), không giá trị rỗng/không còn chữ có dấu trong EN, locale EN dịch đúng, và **không có chữ tiếng Việt viết cứng** trong Blade/JS khảo sát (sau khi bỏ chú thích).
- **Ghi chú:** `ValidationException` messages do Laravel (`validation.php`) dùng bộ dịch riêng của dự án, ngoài phạm vi.

## KS-008 — Payload cấu trúc
- **File sửa:** `SurveyService` (`normalizeStructure($groups, $survey)`: từ chối ID trùng trong payload; ID phải thuộc đúng khảo sát, lựa chọn thuộc đúng câu hỏi, lựa chọn trên câu hỏi mới bị từ chối; ID phải là số nguyên dương; giới hạn độ dài **báo lỗi thay vì cắt âm thầm**: tên phần 255, mô tả phần 2000, câu hỏi 2000, gợi ý 1000, lựa chọn 500, nhãn thang 100; trường không phải chuỗi/kiểu là mảng bị từ chối; toàn bộ kiểm tra chạy **trước** transaction ghi), `SurveyDataProvider::structureIds()`, lang VI/EN.
- **Test:** `test_ks008_*` — ID trùng (phần/câu/lựa chọn), ID thuộc khảo sát khác, lựa chọn sang câu khác, ID không phải số (`1 OR 1=1`), 8 trường vượt giới hạn/sai kiểu, đúng giới hạn thì được, request HTTP thô bỏ qua JS; mỗi trường hợp chứng minh DB **không đổi** (ảnh chụp 3 bảng).

## KS-009 — Audit thao tác nhạy cảm
- **File:** `Support/SurveyAudit.php` (mới), gọi từ `SurveyService` (tạo, sửa thông tin, sửa cấu trúc, chuyển trạng thái, nhân bản, xóa, khôi phục) và `SurveyReportService::deleteParticipation`. Ghi `Log::info('survey.audit')` với hành động, id khảo sát, id phản hồi, người thao tác, thời gian, siêu dữ liệu số lượng/trạng thái. **Không** ghi họ tên, liên hệ, câu trả lời.
- **Test:** `test_ks009_*` kiểm tra có đủ 7 hành động và nhật ký không chứa dữ liệu cá nhân/câu trả lời.
- **Ghi chú:** ghi vào log ứng dụng, chưa vào bảng audit riêng.

## KS-010 / KS-011
Chưa làm / giữ nguyên như bảng trên.

## Ghi chú minh bạch về một thao tác không mong muốn
Khi định dạng mã mới bằng `vendor/bin/pint`, tôi lỡ truyền cả thư mục `Modules/CmsBackend/app/Services` và `app/Support`, nên Pint đã **định dạng lại (chỉ style: khoảng trắng, thứ tự import, kết thúc dòng)** khoảng 25 file có sẵn không thuộc khảo sát (ví dụ `PostService`, `LienHeService`, `HoiDapService`, `DeveloperSettings`, `Social/Facebook/*`, `AdminTheme`, `HomeSettings`…). Không có git nên không hoàn tác được từng byte; tất cả vẫn qua `php -l`, và bộ test sẵn có cho kết quả giống trước (2 test lỗi cũ không liên quan). Bạn nên xem lại diff nếu có hệ quản lý phiên bản bên ngoài. Từ đó tôi chỉ chạy Pint trên file cụ thể.

## Lỗi tự phát hiện trước review (giữ lại để minh bạch)
| Mã | Nguyên nhân | Sửa | Phát hiện bởi |
|---|---|---|---|
| SELF-1 | `Toastr::error()` không tồn tại → 500 khi hành động bị quy tắc nghiệp vụ từ chối. | `Toastr::warning($message)`. | `SurveyAdminReportTest` |
| SELF-2 | Soft delete của `KhaoSatUser` làm bảng điều khiển 500 khi DB chưa migrate. | `KhaoSatDataProvider::getUsersWithFilter` bỏ scope khi thiếu cột (D19). | `PhaseOneIsolationTest` |
| SELF-3 | Lỗi test: cookie không giải mã. | `withCookie`. | `SurveyPublicFlowTest` |

---

# Vòng 2 — xử lý "Codex kiểm tra lại / Quyết định" (KS-001, KS-002, KS-007, KS-008)

Phạm vi theo yêu cầu: chỉ 4 mục này. Không làm KS-010/KS-011, không migrate/build/deploy, không sửa `CODEX-REVIEW.md`, Pint chỉ chạy trên đúng file vừa sửa.

**Kết quả test:** `tests/Feature/Survey` = **102 test, 1149 assertion, 0 lỗi, 1 skipped** (SQLite `:memory:`, PHP 8.3.36). Test skipped là KS-001/composer (xem dưới) — cố ý hiển thị "skipped" thay vì giả vờ đạt. Test mới: `tests/Feature/Survey/SurveyReviewRound2Test.php` (21 test kể cả data provider).

| Mã | Trạng thái (theo agent) |
|---|---|
| KS-001 | **Phần sanitizer đã đạt từ trước; phần dependency CHƯA LÀM ĐƯỢC** — cần chạy Composer (xem dưới) |
| KS-002 | Đã sửa — chờ Codex kiểm tra lại |
| KS-007 | Đã sửa theo quyết định 2026-10-30 — chờ Codex kiểm tra lại |
| KS-008 | Đã sửa — chờ Codex kiểm tra lại |

## KS-001 — dependency `ezyang/htmlpurifier` (CHƯA XONG)
- **Nguyên nhân:** `SurveyHtmlSanitizer` gọi trực tiếp `HTMLPurifier` nhưng `composer.json` không có trong `require` (chỉ có gián tiếp qua `maatwebsite/excel`).
- **Đã làm:** không sửa `composer.json`/`composer.lock` bằng tay (đúng yêu cầu reviewer). Máy này không có lệnh `composer`; việc tải `composer.phar` để chạy bị hệ thống từ chối nên tôi không đi đường vòng.
- **Cần chủ dự án chạy:** `composer require ezyang/htmlpurifier:^4.18` (lock hiện là v4.18.0, nên không đổi phiên bản). Chỉ `composer.json` và `composer.lock` sẽ đổi.
- **Test:** `test_ks001_htmlpurifier_is_a_direct_composer_requirement` — hiện **skipped** có thông điệp rõ; sau khi chạy Composer nó tự chuyển thành kiểm tra thật (require + lock + class tồn tại).
- **Codex kiểm tra lại:** `composer.json` có `ezyang/htmlpurifier` trong `require`, lock cập nhật hợp lệ (`composer validate`), test trên PASS (không skipped).

## KS-002 — quyền cha `khaosat-manager` không thấy nút trong Blade
- **Nguyên nhân:** middleware `cms.check.permission` cho quyền cha bao trùm quyền con, nhưng Blade gọi thẳng `$user->can('quyền-con')` nên người chỉ có quyền cha vào được route mà không thấy nút.
- **File:** mới `Modules/CmsBackend/app/Support/SurveyPermissions.php` (`can`, `canAny`, `map` — ngữ nghĩa: quyền con HOẶC `khaosat-manager`, giống middleware; không mở rộng sang quyền ngoài khảo sát). Sửa 5 Blade trong `resources/views/screens/default/pages/khao-sat/`: `index`, `edit`, `responses`, `statistics`, `_nav` — đều lấy quyền từ `SurveyPermissions::map()`.
- **Test:** `test_ks002_parent_permission_alone_behaves_like_every_child_in_routes_and_views` (vai trò chỉ có `khaosat-manager`: danh sách, trang sửa, phản hồi, thống kê đều thấy đủ nút; quyền con đơn lẻ vẫn không bị mở rộng), `test_ks002_blade_views_do_not_call_can_directly` (chặn tái phát: Blade không được gọi `->can(`). Các test KS-002 vòng 1 (từng quyền con) vẫn đạt.
- **Tác động:** quyền ghi vẫn tách theo route; chỉ đổi cách giao diện tính quyền.
- **Codex kiểm tra lại:** vai trò chỉ có `khaosat-manager` trong trình duyệt/thực tế (giao diện thật dùng Spatie, không phải stub của test).

## KS-007 — khóa quy tắc thu thập sau khi khảo sát đã mở / có lượt tham gia
- **Quy tắc (theo quyết định):** khóa khi trạng thái ≠ Nháp **hoặc** đã có lượt tham gia. Trường khóa: `ks_loai`, `ks_bat_dau`, `ks_cho_phep_an_danh`, `ks_yeu_cau_thong_tin`, `ks_so_lan_gui`, slug (`ks_ma`). Vẫn sửa: tên, mô tả ngắn, giới thiệu, ngày kết thúc, lời cảm ơn, ghi chú. Mở/Tạm dừng: ngày kết thúc **mới** (khác giá trị đang lưu) phải ở tương lai; xóa ngày kết thúc vẫn được; muốn kết thúc ngay dùng chuyển trạng thái Kết thúc.
- **Server:** `SurveyService::guardCollectionRules()` (gọi trong `update()` trước khi ghi) so sánh giá trị gửi lên với giá trị đang lưu và ném `SurveyRuleException` (`collection_locked`, liệt kê tên trường; `end_in_past`). Thêm `collectionLocked()` và `capabilities()['collection_locked']`; `edit_slug` cũng tắt khi bị khóa.
- **Giao diện:** `edit.blade.php` hiện thông báo khóa; các ô khóa bị vô hiệu hóa **và** có `hidden` mang giá trị hiện tại để form vẫn gửi đúng giá trị (ô disabled không được trình duyệt gửi). Lưu ý có chủ đích: request thiếu một trường khóa (ví dụ bỏ `ks_cho_phep_an_danh`) bị coi là thay đổi và bị từ chối, không âm thầm chấp nhận.
- **Lang:** `lang/vi|en/admin.php`: `survey.notice_collection_locked`, `survey.locked_field.*`, `survey.error.collection_locked`, `survey.error.end_in_past` (`SurveyI18nTest` đối chiếu VI/EN vẫn đạt).
- **Test:** Nháp trống sửa tự do; Mở và Tạm dừng — từng trường khóa bị từ chối và bản ghi `ks_khao_sat` giữ nguyên, phần trình bày sửa được; ngày kết thúc quá khứ bị từ chối (Mở, Tạm dừng) còn xóa ngày vẫn được; Nháp có lượt tham gia vẫn khóa; request HTTP giả mạo (từng trường + thiếu trường) bị từ chối và DB không đổi, biểu mẫu đúng như UI thì lưu được; trang sửa hiện thông báo/ô disabled/hidden.
- **Codex kiểm tra lại:** `SurveyService.php` (`guardCollectionRules`, `collectionLocked`), `edit.blade.php`; đặc biệt so sánh định dạng ngày (`Y-m-d H:i`, độ chính xác phút) với giá trị `datetime-local` thật; và thao tác "Nhân bản" vẫn là đường sửa quy tắc.

## KS-008 — container sai kiểu bị lọc bỏ âm thầm
- **Nguyên nhân:** `normalizeStructure()` dùng `array_filter(..., 'is_array')` cho group/question/option nên phần tử sai kiểu bị bỏ; `structure=['chuỗi']` thành cấu trúc rỗng rồi xóa cấu trúc hiện có.
- **Sửa:** `SurveyService::listOfArrays()` — giá trị không phải mảng, hoặc có phần tử không phải mảng, ném `structure_invalid` (đã có sẵn VI/EN). Áp cho danh sách group, `questions`, `options`. Mảng rỗng hợp lệ vẫn là thao tác xóa có chủ đích; khóa vắng mặt/`null` vẫn là rỗng. Mọi kiểm tra chạy trước khi ghi nên DB giữ nguyên.
- **Test:** 10 payload sai kiểu (service, so sánh snapshot 3 bảng `ks_nhom`/`ks_cau_hoi`/`ks_tra_loi`), 5 payload HTTP (`["chuỗi"]`, `questions` là chuỗi, JSON chuỗi, `"x"`, không phải JSON) cộng mảng gửi thẳng; cấu trúc hợp lệ và rỗng vẫn chạy.
- **Codex kiểm tra lại:** `SurveyService.php` `normalizeStructure`/`listOfArrays`; xem `builder.js` không bao giờ gửi `options`/`questions` dạng khác mảng (trình dựng hiện luôn gửi mảng).

## Định dạng mã
Pint chỉ chạy trên: `SurveyReviewRound2Test.php`, `SurveyPermissions.php`, `SurveyService.php`. Pint có thể chuẩn hóa kết thúc dòng của các file này.
