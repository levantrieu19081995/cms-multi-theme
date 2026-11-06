# HOMEPAGE LAYOUT MANAGER — CODEX REVIEW

Ngày kiểm tra: 2026-11-02  
Trạng thái: **CHƯA NGHIỆM THU — CẦN SỬA CÁC MỤC OPEN**

## Kết quả đã đạt

- `HomepageBlockRegistry::selfCheck()` trả về rỗng; nhận đủ 16 Definition và 3 Theme Layout.
- Route Developer Settings/Preview/Apply nằm sau `cms.developer`; snapshot gắn với hash session và token 40 ký tự.
- `node scripts/theme-build.mjs all --check-only` đạt: 62 asset Blade đều có trong build.
- Build thật đạt cho cả ba profile:
  - `default`: 887 manifest entries.
  - `nam-binh-duong`: 884 manifest entries.
  - `ban-de-an`: 884 manifest entries.
- Build chỉ ghi vào `build-exports/themes/*/build`; không ghi đè `public/build`.
- Cả ba theme vẫn ở `legacy`; chưa có migration, install layout, đổi mode hoặc deploy.

## Lỗi cần sửa

### HLM-001 — ĐÃ SỬA (chờ Codex) — Settings theo theme bị option cũ toàn cục ghi đè

Mức độ: **Cao**.

`HomepageSettingsService::read()` đọc `homepage_block_settings` theo `theme_key`, nhưng sau đó `legacyValues()` luôn lấy option cũ toàn cục và ghi đè lên giá trị vừa đọc. `save()` cũng ghi settings theo theme rồi `mirrorLegacy()` ghi lại cùng option toàn cục.

Hậu quả: chỉnh `notice_category`, `document_filter`, lịch hoặc truy xuất ở theme A có thể làm theme B đọc cùng giá trị. Điều này trái mục tiêu mỗi theme có cấu hình nguồn nội dung riêng và tạo hai nguồn sự thật.

Vị trí chính:

- `Modules/CmsFrontend/app/Homepage/HomepageSettingsService.php`: `read()`, `legacyValues()`, `mirrorLegacy()`.
- Các Definition có `legacyBinding`: `InformationBlock`, `DocumentsBlock`, `SchedulesTraceBlock`, `ActivitiesBlock`.

Yêu cầu sửa:

1. Khi theme ở `managed`, `homepage_block_settings` phải là nguồn đọc chính thức theo `(theme_key, block_instance_id)`.
2. Option cũ chỉ dùng để **khởi tạo/migrate một lần** khi settings của theme chưa tồn tại; không ghi đè settings đã có.
3. Việc mirror về option cũ chỉ phục vụ theme `legacy` tương ứng và không được làm theme khác đổi theo. Nếu option cũ không có namespace theme thì không thể dùng làm nguồn đồng bộ hai chiều.
4. Cần có test chứng minh hai theme lưu hai chuyên mục khác nhau và đọc lại đúng, không ảnh hưởng nhau.

### HLM-002 — ĐÃ SỬA (chờ Codex) — Chưa kiểm tra asset/effect trước khi áp dụng

Mức độ: **Trung bình**.

`effects` hiện chỉ là chuỗi mô tả trong Theme Layout. `HomepageLayoutService::validate()` và `HomepageLayoutAdmin::blocking()` chỉ kiểm Definition, zone, variant và file renderer; không kiểm effect/asset mà variant cần.

Hậu quả: Developer vẫn có thể áp dụng variant có renderer tồn tại nhưng thiếu effect/asset cần thiết, trái tiêu chí Master Plan “thiếu renderer/asset/effect phải bị chặn”.

Vị trí chính:

- `Modules/CmsFrontend/app/Homepage/Themes/*HomepageLayout.php`
- `Modules/CmsFrontend/app/Homepage/HomepageLayoutService.php`
- `Modules/CmsBackend/app/Services/Developer/HomepageLayoutAdmin.php`

Yêu cầu sửa:

1. Thay chuỗi mô tả tự do bằng danh sách effect preset/asset key nằm trong allow-list code.
2. Registry phải xác nhận preset được theme build hỗ trợ.
3. Preview/Apply phải báo lỗi và chặn khi preset/asset thiếu.
4. Không lưu JS, CSS path hoặc selector tùy ý trong DB.

### HLM-003 — ĐÃ SỬA (chờ Codex) — Resolver có thể phát sinh truy vấn theo từng instance

Mức độ: **Trung bình**.

`SectionBlock::resolve()` gọi `HomeBlocks::sectionByCategory()` cho từng block `section`. Nếu trang có nhiều instance section chọn category, renderer phát sinh một truy vấn riêng cho từng instance. Điều này không đúng với mô tả “dùng dữ liệu eager-load, không thêm truy vấn/N+1”.

Yêu cầu sửa:

1. Gom category ID của các section đang bật và nạp theo batch trước khi render, hoặc cache kết quả theo category trong request.
2. Thêm test/query-count cho nhiều instance section.

### HLM-004 — ĐÃ SỬA (chờ Codex) — Chưa có test tự động cho chức năng mới

Mức độ: **Cao trước khi migrate**.

Không tìm thấy test cho Registry, migration, install idempotent, layout validation, preview ownership, per-theme settings hoặc renderer managed/legacy.

Tối thiểu cần bổ sung test cho:

- Migration up/down và FK/index.
- `layout-install` chạy hai lần không trùng.
- Validation zone/variant/duplicate/renderer.
- Preview khác session hoặc hết hạn bị từ chối.
- Apply ghi transaction và không đổi Live khi chỉ Preview.
- Settings tách biệt theo theme (HLM-001).
- Tắt block không mất settings; bật lại giữ cấu hình.
- Managed default layout render đủ block đúng thứ tự.

## Giới hạn môi trường kiểm tra

- Migration SQLite tạm chưa chạy được vì PHP CLI hiện thiếu `pdo_sqlite` (`could not find driver`). Đây là giới hạn môi trường, chưa kết luận migration sai.
- Chưa chạy migration trên DB dev, chưa chạy `homepage:layout-install`, chưa chuyển theme sang `managed`, chưa kiểm tra trình duyệt và chưa deploy.

## Điều kiện để Codex kiểm tra vòng tiếp theo

1. Sửa HLM-001 đến HLM-003.
2. Bổ sung test HLM-004 và chạy trên database thử hoặc SQLite có `pdo_sqlite`; không chạy test ghi lên DB dev.
3. Giữ cả ba theme ở `legacy` trong lúc sửa.
4. Không chạy migration/deploy trước khi vòng review tiếp theo đạt.


---

# VÒNG SỬA 1 — BẰNG CHỨNG (HLM-001 → HLM-004)

Trạng thái: **ĐÃ SỬA, CHỜ CODEX KIỂM TRA LẠI**. Ba theme vẫn `legacy`; không chạy migration, không deploy, không ghi gì lên database dev.

## HLM-001 — settings theo theme (đã sửa)

- `HomepageSettingsService::read()`: nguồn đọc là dòng `homepage_block_settings` của đúng `(theme_key, instance)`. Chỉ khi theme CHƯA có dòng mới dùng giá trị khởi tạo từ option cũ (chỉ đọc). `legacyValues()` không còn ghi đè bản đã lưu.
- `save()` chỉ ghi `homepage_block_settings`; **đã xóa `mirrorLegacy()`** — không còn ghi ngược option toàn cục, nên theme A không làm đổi theme B và theme `legacy` giữ option cũ nguyên vẹn.
- Mới `seedFromLegacy(theme)`: khởi tạo MỘT LẦN settings từ option cũ, gọi trong `HomepageLayoutService::installDefaults()`; chạy lại không đè dòng đã có.
- Vì dữ liệu hiển thị của `managed` trước đó lấy từ option toàn cục (qua `HomeController`), thêm lớp áp dụng settings của theme vào dữ liệu: `HomepageDataResolver::mergeAll()` chạy trong `HomePresentation::prepare()` TRƯỚC khi tính khóa cache (nên cache đi theo nội dung của theme). Các Definition `InformationBlock`, `DocumentsBlock`, `NoticeTickerBlock`, `ActivitiesBlock`, `SchedulesTraceBlock`, `SectionBlock` giờ `resolve()` ra `categoryPosts/categoryInfo`, `homepageDocuments`, `homeCategoryTabs`, `homeSchedules/homeTraceLinks`, `homeSections` theo settings của theme. Trang ngôn ngữ khác vẫn bỏ văn bản/lịch/truy xuất như `prepare()` cũ.
- `HomeBlocks` thêm `noticePosts()`, `documents()`, và mở `categoryTabs($ids)`, `schedules()`, `traceLinks()` (cùng quy tắc hiển thị công khai với `HomeController`; `HomeController` không bị sửa).
- Thay đổi hành vi cần Codex biết: `NoticeTickerBlock` lấy chuyên mục từ cụm `information` của CÙNG theme.
- Test: `HomepageCoreTest::test_settings_of_a_theme_are_read_from_that_theme_only_without_touching_legacy_options` (PASS: đọc đúng theo theme, 0 truy vấn option cũ) và `HomepageDatabaseTest::test_settings_are_separate_per_theme_and_never_touch_legacy_options`, `test_install_seeds_settings_once_from_legacy_options` (cần database thử, xem bên dưới).

## HLM-002 — effect/asset (đã sửa)

- Mới `Modules/CmsFrontend/app/Homepage/EffectCatalog.php`: allow-list preset `reveal`, `stagger`, `count-up`, `crossfade-slider`, `gallery-orbit`; mỗi preset có theme được dùng, file nguồn và entry CSS phải có trong `public/build/manifest.json` khi có build (bỏ qua khi Vite dev/chưa build).
- Theme Layout khai báo `effects` là **danh sách KEY preset** (không còn chuỗi mô tả). `HomepageBlockRegistry::effectProblems()` và `selfCheck()` (preset lạ / theme không được phép).
- `HomepageLayoutService::validate()` trả `missing_effect`; `plan()` loại cụm thiếu effect và ghi `issues`; `HomepageLayoutAdmin` hiện variant `ok=false` và chặn Apply (qua `blocking()`); lang `issues.missing_effect` VI/EN.
- Database vẫn chỉ lưu `zone/variant/enabled/sort`; không lưu JS/CSS/selector.
- Test: `test_every_declared_renderer_and_effect_is_available_for_its_theme`, `test_effect_catalog_checks_theme_files_and_build_manifest`, `test_enabling_a_block_without_renderer_or_effect_is_blocked_but_disabling_is_allowed`, `test_plan_excludes_blocks_that_cannot_render_and_reports_why` (PASS).

## HLM-003 — truy vấn theo instance (đã sửa)

- `HomeBlocks::sectionsByCategories(array $ids)`: số truy vấn cố định (chuyên mục, bài đã xếp hạng bằng `ROW_NUMBER`, bài + media/taxonomy) và nhớ kết quả theo request; `sectionByCategory()` dùng lại bộ nhớ này.
- Contract thêm `prefetch(list $allSettings)`; `HomepageDataResolver::mergeAll()` gọi `prefetch` một lần cho mỗi Definition với settings của mọi instance đang bật; `SectionBlock::prefetch()` nạp toàn bộ category một lần.
- Test: `test_resolver_applies_blocks_in_order_batches_prefetch_and_passes_theme` (PASS: prefetch gọi 1 lần cho 3 instance) và `HomepageDatabaseTest::test_section_queries_do_not_grow_with_the_number_of_instances` (so số truy vấn 1 vs nhiều chuyên mục và 0 truy vấn khi render từng instance sau prefetch; cần database thử).

## HLM-004 — test tự động (đã bổ sung)

- `tests/Unit/Homepage/HomepageCoreTest.php` — **không cần database** — 15 test: Registry nhất quán/độc lập theme, renderer + effect khả dụng, thứ tự managed mặc định đúng bố cục khai báo cho cả 3 theme (kế hoạch render), validation zone/variant/duplicate/unknown/theme, thiếu renderer/effect, cụm thiếu trong payload không bị xóa, preview khác session/hết hạn 30 phút/token sai/đã xóa bị từ chối, Apply bị từ chối khi chưa có bảng, schema settings, resolver.
- `tests/Feature/Homepage/HomepageDatabaseTest.php` — 9 test cần bảng thật: migration up/down + index + FK cascade + `down` không đụng bảng nghiệp vụ, `layout-install` chạy hai lần không trùng và không đè bố cục đã chỉnh, Preview không đổi Live, Apply từ chối cấu hình sai không ghi gì và ghi khi hợp lệ, chuyển `managed` cần bố cục hợp lệ và rollback `legacy`, settings tách theo theme, seed một lần, tắt block giữ settings và bật lại khôi phục, số truy vấn của section.

## Kết quả chạy test

Lệnh: `vendor/bin/phpunit tests/Unit/Homepage tests/Feature/Homepage`

```
Tests: 24, Assertions: 166, Skipped: 9.   OK, but some tests were skipped!
```

- 15 test `HomepageCoreTest`: **PASS** (không chạm database).
- 9 test `HomepageDatabaseTest`: **SKIPPED — CHƯA CHẠY ĐƯỢC.** Hai lý do: PHP CLI thiếu `pdo_sqlite` và việc tự tạo database thử trên MySQL dùng chung (`172.16.1.100`) bị hệ thống từ chối nên tôi dừng, không tìm đường vòng. Test tự chặn nếu `HLM_TEST_DATABASE` trùng database dev (`cms_workspace`) và chỉ chạy khi biến này trỏ tới một database thử đã có schema CMS nhưng chưa có 3 bảng của chức năng:
  `HLM_TEST_DATABASE=<db thử> vendor/bin/phpunit tests/Feature/Homepage`
  **Cần người có quyền tạo database thử (hoặc bật `pdo_sqlite` rồi cung cấp schema) để chạy 9 test này; chưa có bằng chứng chạy thật cho migration, install idempotent, apply transaction, settings tách theme và đếm truy vấn trên DB.**
- Kiểm tra thêm: `php -l` toàn bộ file đã sửa, `HomepageBlockRegistry::selfCheck()` rỗng, `panel('ban-de-an')` dựng được (chỉ đọc, `ready=false`, `mode=legacy`).
- `vendor/bin/phpunit` toàn bộ: nhiều test cũ ngoài phạm vi đang FAIL/ERROR (Auth, Profile, PermanentPostDeletion, ScreenThemeHttp, SbutTheme, BanDeAn...). Đã xem `BanDeAnThemeTest`: gọi URL công khai dạng `/cms/tin-tuc` (nay trang công khai ở `/`, `/cms` là admin → 302) và so `version` cũ; tệp test từ 26/10 nên là test lỗi thời, không liên quan Homepage. Không sửa các test đó (ngoài phạm vi).

## Lưu ý còn lại cho Codex

- Kiểm tra bằng HTML thật: dữ liệu `managed` theo settings của theme (đặc biệt `information`/`activities`/`schedules-trace`) so với `legacy` khi settings được seed từ cùng option.
- `HomeBlocks::noticePosts()` / `documents()` sao chép quy tắc truy vấn của `HomeController::index()`; nếu đổi một nơi phải đổi nơi kia (có thể gom lại sau khi nghiệm thu).
- `EffectCatalog` coi `reveal/stagger/count-up` là có sẵn trong mọi theme và chỉ kiểm entry riêng của `crossfade-slider`/`gallery-orbit` trong manifest; chưa kiểm hook HTML của từng cụm.

---

# VÒNG KIỂM TRA 2 — CODEX (2026-11-02)

Trạng thái tổng: **CHƯA NGHIỆM THU**. Không chạy migration, không ghi DB dev, không đổi theme sang `managed` và không deploy.

## Bằng chứng đã chạy lại

Lệnh:

```text
vendor/bin/phpunit tests/Unit/Homepage tests/Feature/Homepage
```

Kết quả xác nhận lại đúng báo cáo của Agent:

```text
Tests: 24, Assertions: 166, Skipped: 9.
```

- 15 test không cần DB: **PASS**.
- 9 test cần bảng thật: **SKIPPED**, nên chưa có bằng chứng chạy thật cho migration, khóa ngoại/index, install idempotent, transaction Apply, tách settings theo theme và query count trên DB.

## Đánh giá lại HLM-001 đến HLM-004

### HLM-001 — ĐẠT Ở MỨC CODE, CHỜ TEST DB

- `stored(theme, instance)` đọc riêng theo theme và trả `null` khi chưa có dòng.
- Option cũ chỉ dùng làm nguồn khởi tạo khi chưa có settings.
- `save()` không còn ghi ngược vào option toàn cục.
- `installDefaults()` seed settings cũ một lần cho từng theme.

Chưa đóng hoàn toàn vì test chứng minh hai theme lưu/đọc độc lập trên DB thật đang bị skip.

### HLM-002 — SỬA MỘT PHẦN, VẪN OPEN

Đã đạt:

- Theme chỉ khai báo effect key trong allow-list.
- Có kiểm theme được phép dùng và file nguồn tồn tại.
- `crossfade-slider` và `gallery-orbit` kiểm CSS entry trong manifest.
- Preview/Apply có thể báo `missing_effect`.

Chưa đạt trọn yêu cầu “single-theme build phải chứa effect có thể bật”:

- `reveal`, `stagger`, `count-up` khai báo `entries => []`, nên `EffectCatalog` không phát hiện được trường hợp profile build thiếu CSS/JS tương ứng.
- Test fake manifest hiện cũng không thể chứng minh ba preset này thật sự có trong build của từng theme.
- Việc file nguồn tồn tại không đồng nghĩa file đó đã được đóng gói trong profile đang phục vụ.

Yêu cầu sửa: EffectCatalog/build registry phải có một nguồn ánh xạ kiểm chứng được cho **tất cả preset**, kể cả effect được import gián tiếp; Preview/Apply phải chặn nếu profile của theme không đóng gói preset đó.

### HLM-003 — ĐẠT Ở MỨC CODE, CHỜ TEST DB

- Resolver gom settings theo Definition và gọi `prefetch()` một lần.
- `SectionBlock` batch toàn bộ category qua `sectionsByCategories()`.
- Kết quả được memo trong request.

Chưa đóng hoàn toàn vì test query-count trên DB thật đang bị skip.

### HLM-004 — CÓ TEST, CHƯA ĐỦ BẰNG CHỨNG

- Đã có 15 test lõi chạy thành công.
- Đã có 9 test DB đúng nhóm cần kiểm tra nhưng chưa chạy được.

Không được coi migration/Apply/managed mode đã nghiệm thu cho đến khi 9 test này chạy PASS trên database thử tách biệt.

## Lỗi mới phát hiện so với MASTER-PLAN

### HLM-005 — BỎ (không làm theo quyết định của chủ dự án 2026-11-02) — Chưa hỗ trợ đúng mô hình Văn bản cạnh Thông báo hoặc thành cụm riêng

Mức độ: **Cao**, vì đây là tiêu chí hoàn thành đã chốt trong Master Plan.

Master Plan yêu cầu cùng Definition `documents` có thể được trình bày:

- Khuyến nông: variant `compact`, nằm cạnh Thông báo.
- Ban Đề án: variant `featured`, là một cụm lớn riêng và có effect riêng.

Code hiện tại:

- `InformationBlock` vẫn là Definition ghép, `covers = ['notice-list', 'documents']`, đồng thời tự truy vấn cả thông báo và văn bản.
- `DefaultHomepageLayout` chỉ khai báo instance `information`; không khai báo instance `documents` độc lập.
- Renderer `default/components/home/information.blade.php` chứa cả hai phần trong cùng một block.
- `DocumentsBlock` riêng chỉ đang được Ban Đề án dùng.

Hậu quả:

- Developer không thể bật/tắt hoặc sắp xếp Văn bản độc lập với Thông báo ở theme Khuyến nông.
- Không thể dùng đúng một Definition `documents` rồi chọn variant/zone khác nhau theo theme như thiết kế đã chốt.
- Cài đặt trang chủ vẫn thấy cấu hình thông báo và văn bản bị gắn vào một cụm ghép.

Yêu cầu sửa:

1. Tách Definition dữ liệu `notice-list` và `documents` thành hai block độc lập.
2. Khuyến nông khai báo hai instance độc lập nhưng renderer/zone phải cho phép trình bày cạnh nhau, giữ nguyên HTML và giao diện hiện tại.
3. Ban Đề án tiếp tục dùng `documents` variant `featured` như cụm lớn riêng.
4. Developer phải bật/tắt, kéo thứ tự và xem Preview từng block độc lập.
5. Cài đặt trang chủ sinh field chọn chuyên mục Thông báo và bộ lọc Văn bản theo từng block đang bật.
6. Legacy mode giữ nguyên giao diện trong suốt quá trình chuyển đổi.

### HLM-006 — BỎ (không làm theo quyết định của chủ dự án 2026-11-02) — Chưa tạo được instance mới từ Definition có sẵn mà không sửa code

Mức độ: **Trung bình**.

Master Plan yêu cầu cùng Definition có nhiều instance (ví dụ nhiều `article-list`) và “thêm instance từ Definition có sẵn không cần sửa controller/Blade”. Hiện tại:

- `HomepageBlockRegistry::instanceKeys()` chỉ lấy instance đã viết cứng trong `ThemeLayout::blocks()`.
- `ensureInstances()` chỉ tạo các instance viết cứng đó.
- Panel Developer cũng chỉ liệt kê `layout->blocks()`.

Vì vậy muốn thêm một cụm bài viết chọn chuyên mục khác vẫn phải sửa Theme Layout trong code. Cần bổ sung luồng tạo instance từ Definition trong Developer Settings, nhưng vẫn chỉ cho chọn renderer/zone/variant mà theme đã khai báo và không lưu class/path/selector tùy ý vào DB.

## Kết luận vòng 2

- HLM-001 và HLM-003: sửa đúng ở mức code, chờ DB test.
- HLM-002: còn thiếu kiểm chứng build profile cho ba effect dùng chung.
- HLM-004: test đã có nhưng 9 test quan trọng chưa chạy.
- HLM-005 và HLM-006: **đã bỏ khỏi phạm vi** theo quyết định của chủ dự án (chỉ hoàn thiện phần đang có); cụm `information` giữ là cụm ghép Thông báo + Văn bản, chưa hỗ trợ tạo thêm instance từ giao diện.
- Chưa chuyển bất kỳ theme nào sang `managed`.

---

# VÒNG SỬA 2 — "LƯU THÀNH CÔNG NHƯNG GIAO DIỆN KHÔNG ĐỔI"

Phạm vi: chỉ hoàn thiện phần đang có. HLM-005/006 đã bỏ. Không đổi theme sang `managed`, không migrate thêm, không deploy.

## Nguyên nhân (đã xác định bằng đọc DB dev, chỉ đọc)

- DB dev hiện có 21 instance, 8 dòng `homepage_theme_blocks` (theme `default`, được lưu lúc 20:59:48, trong đó `information` sort 10 đứng trước `news` sort 20), 0 dòng `homepage_block_settings`, và **không có dòng `cms_homepage_layout_mode.default`** nên theme `default` đang ở chế độ `legacy`.
- Nút "Áp dụng" chỉ ghi bố cục (Live trong DB). Trang chủ công khai chỉ đi theo bố cục khi chế độ là `managed`; ở `legacy` nó vẫn dùng `home-content.blade.php`. Giao diện báo "thành công" nhưng không nói điều này → trông như lỗi.
- Kiểm chứng đường `managed` bằng script chỉ-đọc (snapshot trong bộ nhớ, không ghi DB): HTML trang chủ Khuyến nông `legacy` và `managed` **cùng độ dài 50045 ký tự, khác duy nhất ở thứ tự cụm (Thông báo và văn bản đứng trước Tin tức đúng như bố cục đã lưu)**. Tức là cơ chế `managed` hoạt động; thiếu chỉ là bước chuyển chế độ.
- 0 dòng settings vì bố cục được cài trước khi có `seedFromLegacy()` (vòng sửa 1). Chạy lại `homepage:layout-install default` sẽ seed một lần, không đè bố cục đã chỉnh.

## Đã sửa (giao diện, không đổi logic render)

- `HomepageLayoutController::apply()`: sau khi áp dụng, nếu theme đang `legacy` hiện cảnh báo rõ "bố cục đã lưu nhưng trang chủ công khai vẫn Legacy"; nếu bấm nút mới "Áp dụng và dùng bố cục quản lý" thì áp dụng rồi chuyển `managed` (có `DeveloperAudit`). Redirect giữ đúng theme đang cấu hình.
- Trang Review: ghi chú chế độ Legacy và thêm nút "Áp dụng và dùng bố cục quản lý" (chỉ hiện khi chưa `managed`); `HomepageLayoutAdmin::review()` trả thêm `mode`.
- Tab Bố cục trang chủ: banner cảnh báo khi bố cục đã cài nhưng chế độ còn `legacy`.
- Lang VI/EN: `applied_but_legacy`, `apply_and_switch`, `legacy_review_note`, `legacy_alert`.
- Backup: `storage/app/backup-homepage-layout-manager-20261102-210524`.

## Chưa làm / lưu ý

- HLM-002 (preset `reveal/stagger/count-up` chưa kiểm được trong build profile) vẫn OPEN, không thuộc yêu cầu này.
- Chưa mở trình duyệt; chưa chạy test DB (không có database thử). Cần Codex/người dùng bấm thử: Áp dụng ở theme `legacy` thấy cảnh báo; "Áp dụng và dùng bố cục quản lý" thì trang chủ đổi thứ tự.

---

# VÒNG 3 — GỠ LEGACY VÀ TỐI ƯU HIỆU ỨNG (quyết định của chủ dự án)

- **Đã gỡ chế độ Legacy.** Trang chủ của mọi theme luôn dựng theo bố cục (database; chưa cài thì bố cục mặc định trong code). Đã xóa `home-content.blade.php` của 3 theme, `homepage:layout-mode`, nút/route/lang đổi chế độ, form Cài đặt trang chủ cố định (chỉ còn form động) và `home-effects.js`. Đã đối chiếu HTML trang đầy đủ Legacy so với Managed trước khi xóa: cả 3 theme giống hệt (chỉ khác dấu thời gian meta và một khoảng trắng giữa hai thẻ block của Ban Đề án).
- **HLM-002 đóng bằng cách làm cho nó không thể xảy ra:** effect thuộc khung dùng chung. `scripts/theme-profiles.mjs` (`sharedEffectEntries`) đóng gói CSS reveal/stagger/gallery-orbit/crossfade-slider và `effects/auto/*.js` trong MỌI profile; `EffectCatalog` bỏ danh sách "theme được dùng" và kiểm file nguồn + entry trong manifest. `node scripts/theme-build.mjs all --check-only` và từng theme (`default`, `nam-binh-duong`, `ban-de-an`) đều đạt (64/57/55/55 asset `@vite`).
- **Nạp hiệu ứng theo kế hoạch cụm:** `HomePresentation` tính `$homepageEffectEntries` từ các cụm đang bật; `home.blade.php` của mỗi theme chỉ `@vite` entry cần thiết. Khuyến nông chỉ còn nạp `crossfade-slider` (CSS + JS tự khởi chạy); không còn nạp `gallery-orbit` vì không cụm nào dùng. Khai báo effect của Theme Layout đã chỉnh theo hook HTML thật của từng partial.
- Cache layout đổi sang `cms.homepage-layout.v2.` vì kế hoạch có thêm khóa `effects` (cache v1 cũ sẽ thiếu hiệu ứng).
- Kiểm tra đã chạy: `vendor/bin/phpunit tests/Unit/Homepage tests/Feature/Homepage` (19 đạt, 9 bỏ qua do chưa có database thử), render chỉ-đọc 3 theme qua đường duy nhất (kích thước trang Nam Bình Dương 59685 và Ban Đề án 62206 ký tự đúng bằng bản Managed đã đối chiếu), `--check-only` 4 profile. Chưa build thật, chưa mở trình duyệt.
