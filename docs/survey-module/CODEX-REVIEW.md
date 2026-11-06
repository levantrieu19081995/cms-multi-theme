# CODEX REVIEW - Module Khảo sát

Ngày rà soát: 2026-10-30  
Phạm vi: đối chiếu code thật với `00-MASTER-PLAN.md`, báo cáo Phase 1-5 và kiểm thử độc lập.  
Nguyên tắc: file này chỉ ghi nhận lỗi/điểm cần sửa; không thay đổi code nghiệp vụ, migration hay dữ liệu.

## Kết quả tổng quát

- Kiểm tra lại vòng 3 ngày 2026-11-01: **PASS - 102 tests, 1.151 assertions, không còn test bị bỏ qua** trên SQLite memory, PHP 8.3.36.
- Chưa chạy migration trên database thật, chưa build giao diện, chưa kiểm thử trình duyệt và chưa deploy.
- Kết luận hiện tại: **CHƯA NÊN chạy migration production**. Cần hoàn thành KS-010 (MySQL tạm + trình duyệt) trước.

## P1 - Bắt buộc sửa trước khi nghiệm thu

### KS-001 - Stored XSS ở nội dung giới thiệu khảo sát

**Trạng thái:** VERIFIED 2026-11-01  
**Mức độ:** P1 / Security

**Vị trí:**

- `Modules/CmsBackend/app/Services/SurveyService.php:98-108`
- `Modules/CmsFrontend/resources/views/screens/default/pages/khao-sat/gioi-thieu.blade.php:11`

**Hiện trạng:** `cleanHtml()` lọc HTML bằng ba biểu thức chính quy, sau đó Blade in thẳng bằng `{!! ... !!}`. Cách này chỉ chặn một số mẫu đơn giản và có thể bị vượt qua bằng HTML không cân bằng, URL được mã hóa entity, thẻ/thuộc tính nguy hiểm khác. Ví dụ `href="java&#x73;cript:..."` không khớp biểu thức hiện tại nhưng trình duyệt có thể giải mã khi hiển thị.

**Yêu cầu sửa:**

1. Không dùng regex tự viết để làm bộ lọc bảo mật HTML.
2. Dùng bộ lọc allow-list đã được kiểm chứng. Dự án đã có dependency `ezyang/htmlpurifier`; cấu hình rõ các thẻ và thuộc tính được phép.
3. Loại bỏ script, event handler, `javascript:`, `data:` nguy hiểm, iframe/object/embed/style và HTML lỗi có khả năng thực thi.
4. Bổ sung test cho URL entity-obfuscated, HTML không đóng thẻ, SVG/event handler và các payload thông dụng; test phải xác nhận đầu ra công khai không thực thi được mã.

**Tiêu chí đóng lỗi:** các payload nguy hiểm bị loại bỏ bằng sanitizer allow-list; nội dung định dạng hợp lệ vẫn hiển thị đúng; test bảo mật mới PASS.

**Codex kiểm tra lại 2026-10-30:** `SurveyHtmlSanitizer` dùng HTMLPurifier allow-list và lọc lại dữ liệu cũ lúc hiển thị; test payload bảo mật đạt. Tuy nhiên code gọi trực tiếp `HTMLPurifier` nhưng `composer.json` chưa khai báo `ezyang/htmlpurifier` trong `require`. Gói hiện tồn tại chỉ vì `maatwebsite/excel` kéo gián tiếp qua PhpSpreadsheet. Cần thêm dependency trực tiếp bằng Composer và cập nhật lock hợp lệ; không chỉnh lock thủ công.

**Codex xác nhận 2026-11-01:** đã chạy Composer thật; `composer.json` khai báo trực tiếp `ezyang/htmlpurifier:^4.18`, lock hợp lệ ở `v4.19.1`, autoload tạo thành công. `composer validate --no-check-publish` đạt (chỉ còn cảnh báo constraint chính xác có sẵn của `maatwebsite/excel`). Toàn bộ `tests/Feature/Survey` đạt 102/102 test, 1.151 assertions, không skip. KS-001 được đóng.

---

### KS-002 - Quyền phản hồi/thống kê thấy menu nhưng không vào được danh sách khảo sát

**Trạng thái:** VERIFIED 2026-11-01  
**Mức độ:** P1 / Authorization UX

**Vị trí:**

- `Modules/CmsBackend/routes/web.php:76`
- `Modules/CmsBackend/routes/web.php:90-94`
- `Modules/CmsBackend/resources/menu/project-verticalMenu.json:87-93`
- `Modules/CmsBackend/resources/views/screens/default/pages/khao-sat/index.blade.php:4-10`

**Hiện trạng:** menu được hiện cho `khaosat-response-view` và `khaosat-statistics-view`, nhưng route danh sách `/cms/khao-sat` chỉ chấp nhận `khaosat-view`, `khaosat-create` hoặc `khaosat-update`. Người chỉ được giao quyền xem phản hồi hoặc thống kê nhìn thấy menu nhưng nhận 403 và không có đường chọn khảo sát để vào chức năng được cấp.

**Yêu cầu sửa:**

1. Cho phép truy cập trang danh sách với mọi quyền nghiệp vụ cần dùng danh sách khảo sát: view/create/update/publish/delete/response-view/response-delete/statistics-view/export; hoặc tạo trang đích riêng phù hợp cho từng quyền.
2. Trong danh sách, chỉ hiện đúng nút/hành động mà người dùng có quyền.
3. Không tự động mở rộng quyền ghi dữ liệu; chỉ sửa khả năng điều hướng và đọc danh sách tối thiểu.
4. Bổ sung test cho vai trò chỉ có `khaosat-response-view`, chỉ có `khaosat-statistics-view`, chỉ có `khaosat-export` và chỉ có `khaosat-delete`.

**Tiêu chí đóng lỗi:** mỗi quyền độc lập truy cập được đúng màn hình cần thiết, không gặp ngõ cụt 403 và không nhìn thấy hành động ngoài quyền.

**Codex kiểm tra lại 2026-10-30:** các quyền con độc lập đã vào được danh sách và route ghi vẫn tách quyền đúng. Còn thiếu trường hợp chỉ có quyền cha `khaosat-manager`: middleware cho quyền cha bao trùm quyền con, nhưng các Blade khảo sát gọi trực tiếp `$user->can('quyền-con')`, nên người chỉ có quyền cha vào được route nhưng không thấy các nút quản lý. Cần một helper quyền khảo sát dùng chung (tương tự `SocialPermissions`) để route và view có cùng ngữ nghĩa, đồng thời thêm test vai trò chỉ có `khaosat-manager`.

**Codex xác minh vòng 2 ngày 2026-11-01:** `SurveyPermissions` đã thống nhất ngữ nghĩa quyền cha/quyền con cho 5 Blade khảo sát; quyền cha thấy đủ hành động, quyền con đơn lẻ không bị mở rộng. Có test route/view và test ngăn Blade gọi `->can()` trực tiếp. Đạt.

---

### KS-003 - Có thể phát hành khảo sát nhưng trang công khai không có câu hỏi

**Trạng thái:** VERIFIED 2026-10-30  
**Mức độ:** P1 / Business rule

**Vị trí:**

- `Modules/CmsBackend/app/Services/SurveyService.php:188-203`
- `Modules/CmsBackend/app/Services/SurveyService.php:282-293`
- `Modules/CmsBackend/app/Services/SurveyFormService.php:43-60`
- `Modules/CmsBackend/app/DataProviders/SurveyDataProvider.php:114-117`

**Hiện trạng:** điều kiện phát hành chỉ đếm câu hỏi đang bật. Một câu hỏi lựa chọn có thể có đủ hai lựa chọn trong dữ liệu nhưng tất cả lựa chọn đều bị tắt. Câu hỏi đó vẫn được tính là đang hoạt động khi phát hành, trong khi `SurveyFormService` bỏ hẳn câu hỏi khỏi giao diện vì không có lựa chọn đang bật. Kết quả là khảo sát được mở nhưng schema công khai có `question_count = 0` và người dùng có thể hoàn thành lượt rỗng.

**Yêu cầu sửa:**

1. Kiểm tra khả năng phát hành dựa trên schema thực tế dùng cho trang công khai, không chỉ đếm `ch_trang_thai`.
2. Bắt buộc có ít nhất một câu hỏi thực sự render được.
3. Với câu hỏi single/multiple đang bật, yêu cầu tối thiểu **hai lựa chọn đang bật** trước khi phát hành.
4. Bổ sung test: tất cả lựa chọn tắt; chỉ một lựa chọn bật; câu hỏi bị tắt; khảo sát hợp lệ.

**Tiêu chí đóng lỗi:** không thể phát hành khảo sát không có câu hỏi render được; lỗi trả về rõ ràng bằng VI/EN.

**Codex kiểm tra lại 2026-10-30:** điều kiện phát hành dùng cùng schema công khai và kiểm tra tối thiểu hai lựa chọn đang bật; các trường hợp lỗi/hợp lệ đã có test hồi quy. Đạt.

## P2 - Cần sửa trước khi đưa vào sử dụng thật

### KS-004 - Giới hạn số lần gửi có race condition

**Trạng thái:** FIXED - PENDING MYSQL (gộp xác minh vào KS-010)  
**Mức độ:** P2 / Data integrity

**Vị trí:**

- `Modules/CmsFrontend/app/Services/SurveyPublicService.php:102-131`
- `Modules/CmsFrontend/app/Services/SurveyPublicService.php:203-226`
- `Modules/CmsFrontend/app/Services/SurveyPublicService.php:283-289`

**Hiện trạng:** hệ thống đếm số lượt hoàn thành theo fingerprint rồi mới ghi. Khi hai lượt gửi song song, cả hai transaction có thể cùng thấy số lượng chưa chạm giới hạn và cùng hoàn thành. Khóa hiện tại chỉ khóa từng participation, không khóa một tài nguyên chung theo khảo sát/fingerprint.

**Yêu cầu sửa:** bảo đảm kiểm tra giới hạn và chuyển sang trạng thái hoàn thành là thao tác nguyên tử. Có thể khóa một dòng ổn định chung (ví dụ dòng khảo sát trong transaction), dùng advisory lock phù hợp DB, hoặc thiết kế ràng buộc dữ liệu tương ứng. Rate limit HTTP không được xem là thay thế cho tính toàn vẹn DB.

**Tiêu chí đóng lỗi:** hai request đồng thời không thể làm vượt `ks_so_lan_gui`; có kiểm thử concurrency hoặc kiểm thử mức service chứng minh cơ chế khóa.

**Codex kiểm tra lại 2026-10-30:** thứ tự khóa khảo sát → participation nằm trong transaction và kiểm tra giới hạn diễn ra sau khóa. Thiết kế hợp lý cho MySQL/InnoDB, nhưng SQLite không kiểm chứng được `FOR UPDATE` hay hai request đồng thời. Chỉ chuyển VERIFIED sau bài test concurrency trên MySQL tạm ở KS-010.

---

### KS-005 - Công tắc khảo sát theo theme đang hard-code, chưa quản lý từ Developer Settings

**Trạng thái:** ACCEPTED BY DESIGN 2026-10-30  
**Mức độ:** P2 / Architecture

**Vị trí:**

- `Modules/CmsBackend/config/features.php:17-22`
- `Modules/CmsBackend/app/Support/ThemeFeatures.php:17-35`

**Hiện trạng:** khảo sát được bật cho Nam Bình Dương và Ban Đề án bằng mảng hard-code trong code. Muốn bật cho theme mới phải sửa file và triển khai lại. Developer Settings hiện chỉ phản ánh trạng thái/quyền, chưa phải nơi quản lý công tắc này.

**Yêu cầu xác nhận:** nếu chủ đích là cấu hình kỹ thuật cố định theo source thì cập nhật tài liệu để nói rõ. Nếu Developer Settings phải quản lý chức năng của mỗi theme, cần đưa registry này vào nguồn cấu hình/manifest chung đang dùng, không tạo hai nguồn sự thật song song.

**Tiêu chí đóng lỗi:** chỉ có một nguồn sự thật cho feature availability; theme mới có quy trình bật khảo sát rõ ràng và không gắn dữ liệu khảo sát với theme/project.

**Quyết định 2026-10-30:** giữ `config/features.php` làm registry kỹ thuật cố định cho việc **theme nào có code/chức năng Khảo sát**. Developer Settings/permission manifest tiếp tục quản lý **quyền nào được đưa ra cho Admin phân quyền trong deployment**. Đây là hai tầng khác nhau, không phải hai nguồn cho cùng một quyết định. Không thêm `project_key`/`theme_key` vào dữ liệu khảo sát. Theme mới muốn hỗ trợ Khảo sát phải đăng ký một lần trong registry source, sau đó Developer bật các quyền cần dùng trong manifest. Cần ghi rõ quy ước này trong README/DECISIONS; không đưa công tắc theme→feature vào DB.

---

### KS-006 - Thiếu toàn bộ bản dịch quản trị tiếng Anh của module mới

**Trạng thái:** VERIFIED 2026-10-30  
**Mức độ:** P2 / i18n

**Vị trí:**

- Có nhóm mới tại `lang/vi/admin.php:966` và `lang/vi/admin.php:1084`
- Không có nhóm tương ứng trong `lang/en/admin.php`

**Hiện trạng:** trang công khai có `lang/en/survey.php`, nhưng các khóa `admin.survey.*` và `admin.survey_report.*` chỉ có tiếng Việt. Khi quản trị chuyển EN, giao diện có thể hiện khóa dịch hoặc fallback tiếng Việt.

**Yêu cầu sửa:** bổ sung đủ cấu trúc khóa tương ứng sang `lang/en/admin.php`; không viết cứng tiếng Việt trong Blade/JS; bổ sung kiểm tra đối chiếu hai cây khóa VI/EN.

**Tiêu chí đóng lỗi:** mọi màn danh sách, tạo/sửa, phản hồi, thống kê, export và thông báo lỗi hiển thị tiếng Anh đầy đủ khi locale EN.

**Codex kiểm tra lại 2026-10-30:** cây khóa `admin.survey` và `admin.survey_report` đã có ở EN; kiểm tra đối chiếu VI/EN và quét Blade/JS đều đạt. Bộ test tổng đạt.

---

### KS-007 - Cho phép đổi quy tắc thu thập dữ liệu sau khi khảo sát đã mở

**Trạng thái:** VERIFIED 2026-11-01  
**Mức độ:** P2 / Data consistency

**Vị trí:** `Modules/CmsBackend/app/Services/SurveyService.php:56-95`

**Hiện trạng:** miễn khảo sát chưa kết thúc, form update có thể đổi loại khảo sát, ẩn danh, yêu cầu thông tin, số lần gửi và các mốc thời gian. Đây không chỉ là metadata trình bày; thay đổi giữa lúc đang thu thập làm các phản hồi trong cùng khảo sát tuân theo quy tắc khác nhau.

**Yêu cầu xác nhận/sửa:** sau khi Open hoặc sau khi đã có phản hồi, khóa các trường ảnh hưởng cách thu thập (`ks_cho_phep_an_danh`, `ks_yeu_cau_thong_tin`, `ks_so_lan_gui`, loại khảo sát; cân nhắc ngày bắt đầu). Chỉ cho sửa nội dung trình bày, ngày kết thúc và lời cảm ơn theo quy tắc đã thống nhất.

**Tiêu chí đóng lỗi:** quy tắc nào được phép đổi theo từng trạng thái được ghi rõ và được chặn ở server, không chỉ vô hiệu hóa trên giao diện.

**Quyết định 2026-10-30:** sau khi khảo sát rời trạng thái Nháp **hoặc đã có lượt tham gia**, khóa ở server các trường `ks_loai`, `ks_bat_dau`, `ks_cho_phep_an_danh`, `ks_yeu_cau_thong_tin`, `ks_so_lan_gui` và slug. Vẫn cho sửa tên, mô tả ngắn, giới thiệu, ngày kết thúc, lời cảm ơn và ghi chú. Với khảo sát Mở/Tạm dừng, ngày kết thúc mới phải ở tương lai; muốn kết thúc ngay phải dùng chuyển trạng thái Kết thúc. Giao diện cần thể hiện trường bị khóa, nhưng server phải so sánh/từ chối thay đổi kể cả request thủ công. Thêm test cho Draft, Open, Paused, có lượt tham gia và request giả mạo.

**Codex xác minh vòng 2 ngày 2026-11-01:** khóa được thực thi ở service trước khi ghi; trạng thái Nháp/Mở/Tạm dừng, Nháp đã có lượt, ngày kết thúc và request giả mạo đều có test. Giao diện gửi lại giá trị khóa bằng hidden field và hiển thị trạng thái disabled. Nhân bản vẫn tạo bản Nháp để thay đổi quy tắc. Đạt.

---

### KS-008 - Payload cấu trúc chưa kiểm tra ID trùng và giới hạn chuỗi chưa đồng nhất ở server

**Trạng thái:** VERIFIED 2026-11-01  
**Mức độ:** P2 / Input integrity

**Vị trí:**

- `Modules/CmsBackend/app/Services/SurveyService.php:149-227`
- `Modules/CmsBackend/app/DataProviders/SurveyDataProvider.php:137-218`

**Hiện trạng:** JSON gửi thẳng có thể lặp cùng group/question/option ID nhiều lần. DataProvider sẽ cập nhật lại cùng bản ghi nhiều lần và kết quả cuối phụ thuộc thứ tự payload. Ngoài ra mô tả nhóm, nội dung/mô tả câu hỏi không có giới hạn server tương ứng; `maxlength` phía trình duyệt không đủ để bảo vệ endpoint.

**Yêu cầu sửa:** từ chối ID trùng trong cùng payload; xác minh ID thuộc đúng khảo sát/câu hỏi; áp giới hạn độ dài server khớp schema/UI cho mọi trường cấu trúc; bổ sung test request thủ công bỏ qua JavaScript.

**Tiêu chí đóng lỗi:** payload ID trùng hoặc chuỗi vượt giới hạn trả validation error rõ ràng và transaction không ghi dở dang.

**Codex kiểm tra lại 2026-10-30:** kiểm tra ID, ownership và giới hạn chuỗi đã đạt. Còn một lỗ hổng: `normalizeStructure()` dùng `array_filter(..., 'is_array')` cho group/question, nên phần tử sai kiểu bị âm thầm bỏ qua. Payload như `structure = ['chuỗi']` có thể chuẩn hóa thành cấu trúc rỗng rồi xóa cấu trúc đang có; `questions = 'chuỗi'` cũng có thể làm rỗng câu hỏi của nhóm. Phải từ chối mọi group/question/option container sai kiểu thay vì lọc bỏ, giữ DB nguyên vẹn, và thêm test HTTP/service cho các trường hợp này.

**Codex xác minh vòng 2 ngày 2026-11-01:** `listOfArrays()` từ chối container và phần tử sai kiểu cho group/question/option trước transaction ghi; test service và HTTP chụp ba bảng xác nhận dữ liệu không đổi. Mảng rỗng vẫn được giữ làm thao tác xóa có chủ đích. Đạt.

## P3 - Khuyến nghị hoàn thiện

### KS-009 - Chưa audit đầy đủ thao tác nhạy cảm

**Trạng thái:** VERIFIED 2026-10-30  
**Mức độ:** P3

Export đã có log, nhưng chưa thấy audit tương xứng cho xóa phản hồi, xóa/khôi phục khảo sát và chuyển trạng thái. Nên ghi người thao tác, khảo sát, response (nếu có), thời gian và hành động; tuyệt đối không ghi nội dung trả lời nhạy cảm vào log.

**Codex kiểm tra lại 2026-10-30:** đã ghi `survey.audit` cho các thao tác nhạy cảm và test xác nhận không đưa họ tên, liên hệ hay câu trả lời vào log. Dùng application log được chấp nhận cho giai đoạn này; bảng audit riêng là nâng cấp sau.

### KS-010 - Khoảng trống kiểm thử môi trường thật

**Trạng thái:** OPEN  
**Mức độ:** P3 / Verification

Test tự động hiện chạy tốt trên SQLite memory, nhưng chưa chứng minh migration và query tương thích database production, chưa kiểm thử trình duyệt cho builder/drag-drop/responsive, chưa build asset. Sau khi sửa P1/P2 cần thực hiện trên bản sao DB hoặc database tạm cùng engine/version production; không chạy thẳng trên dữ liệu thật.

### KS-011 - Giao diện công khai mới chỉ dùng layout độc lập

**Trạng thái:** OPEN - limitation  
**Mức độ:** P3 / UX

Trang khảo sát dùng layout riêng và biến màu theo theme, chưa tích hợp header/footer/component đầy đủ của từng theme. Cách này dùng được cho phiên bản đầu, nhưng cần ghi rõ là fallback chung; khi khách yêu cầu nhận diện hoàn chỉnh thì thêm presentation theo theme, không nhân đôi service/controller/data.

## Thứ tự Agent nên sửa

1. KS-001, KS-002, KS-003.
2. Chạy lại toàn bộ test và bổ sung test hồi quy cho ba lỗi trên.
3. KS-004, KS-006, KS-008.
4. Chốt quyết định KS-005 và KS-007 với chủ dự án rồi mới sửa.
5. Hoàn thiện KS-009 đến KS-011 và kiểm tra môi trường thật.

## Quy tắc cập nhật file này

- Agent không xóa nội dung lỗi cũ.
- Khi sửa xong một lỗi, đổi `OPEN` thành `FIXED BY AGENT`, ghi file đã sửa và test đã thêm ngay dưới mục đó.
- Codex sẽ kiểm tra lại code thật; chỉ Codex đổi trạng thái thành `VERIFIED` hoặc `REOPENED`.
- Không chạy migration production, không build/deploy và không sửa dữ liệu chỉ để đóng review.
