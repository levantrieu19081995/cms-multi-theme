# HOMEPAGE LAYOUT MANAGER — MASTER PLAN

> Trạng thái: tài liệu kiến trúc đã thống nhất, **chưa triển khai code**.
>
> Đây là nguồn định hướng chính thức cho chức năng Quản lý bố cục trang chủ. Khi triển khai không tự ý đổi ranh giới giữa dữ liệu, theme và effect. Nếu có thay đổi quyết định, phải cập nhật file này trước.
>
> Phạm vi file bị ảnh hưởng và quy trình backup/hoàn tác bắt buộc được quy định tại `IMPACT-AND-BACKUP.md`.

## 1. Mục tiêu

Xây dựng `Homepage Layout Manager` để:

- Developer chọn cụm nào xuất hiện trên trang chủ của từng theme.
- Developer sắp xếp cụm theo vùng bố cục và thứ tự cho phép.
- Quản trị viên thường chỉ cấu hình nguồn nội dung của các cụm đã được Developer bật.
- Mỗi theme giữ được HTML, bố cục, màu sắc và hiệu ứng riêng.
- Thêm một loại cụm mới chỉ cần khai báo tập trung một lần, không bổ sung danh sách cứng rải rác trong controller hoặc Blade cài đặt.
- Bật/tắt, đổi thứ tự và đổi nội dung không yêu cầu build lại.
- Chỉ cần build/deploy khi lập trình viên bổ sung loại block, renderer hoặc effect mới.

## 2. Ba nguồn sự thật

Hệ thống phải phân tách rõ:

1. **Developer Settings** quyết định block nào tồn tại trên trang chủ của theme, nằm ở zone nào và thứ tự bao nhiêu.
2. **Cài đặt trang chủ** quyết định block đang bật lấy nội dung gì, ví dụ chuyên mục, số lượng bài hoặc kiểu sắp xếp.
3. **Theme** quyết định block được trình bày và chạy hiệu ứng như thế nào.

Không được để một thông tin có hai nguồn cấu hình song song. Khi một block đã chuyển sang Layout Manager, cấu hình cũ tương ứng phải được migrate rồi loại khỏi luồng đọc/ghi chính thức.

## 3. Khái niệm chính

### 3.1. Block Definition

Là loại cụm hệ thống biết cách xử lý, ví dụ:

- `article-list`
- `featured-news`
- `notice-list`
- `documents`
- `events`
- `gallery`
- `videos`
- `statistics`
- `links`

Definition là code, không phải một dòng tùy ý trong database. Definition phải khai báo tối thiểu:

- `key()` — mã ổn định, không đổi sau khi phát hành.
- Tên và mô tả VI/EN bằng lang key.
- Icon.
- Data provider/resolver.
- Schema cấu hình nội dung.
- Route quản lý liên quan nếu có.
- Những theme hỗ trợ hoặc cơ chế kiểm tra renderer của theme.
- Giá trị mặc định an toàn.

### 3.2. Block Instance

Là một cụm cụ thể được tạo từ Definition.

Ví dụ cùng Definition `article-list` có thể có các instance:

- `agriculture-news`
- `science-news`
- `admission-news`

Nhờ vậy không phải viết ba block code chỉ vì chúng chọn ba chuyên mục khác nhau.

### 3.3. Theme Layout

Mỗi theme khai báo các vùng bố cục (`zone`) mà nó hỗ trợ, ví dụ:

- `hero`
- `before-main`
- `main`
- `sidebar`
- `after-main`
- `footer`

Theme được phép có zone riêng. Developer chỉ được đưa block vào zone mà theme cho phép.

### 3.4. Renderer và Variant

Cùng một Definition có renderer riêng cho từng theme. Variant là kiểu hiển thị hợp lệ do code của theme khai báo, không phải tên CSS tùy ý nhập từ giao diện.

Ví dụ `documents`:

- Theme Khuyến nông: zone `notice-documents`, variant `compact`, nằm cạnh Thông báo.
- Theme Ban Đề án: zone `documents`, variant `featured`, là một cụm lớn riêng.

Nguồn dữ liệu và chức năng quản lý văn bản vẫn dùng chung.

## 4. Cấu trúc code mục tiêu

```text
Modules/CmsFrontend/app/Homepage/
├── Contracts/
│   ├── HomepageBlockDefinition.php
│   └── HomepageThemeLayout.php
├── Definitions/
│   ├── ArticleListBlock.php
│   ├── NoticeBlock.php
│   ├── DocumentsBlock.php
│   ├── EventsBlock.php
│   ├── GalleryBlock.php
│   └── VideoBlock.php
├── Themes/
│   ├── DefaultHomepageLayout.php
│   ├── NamBinhDuongHomepageLayout.php
│   └── BanDeAnHomepageLayout.php
├── HomepageBlockRegistry.php
├── HomepageLayoutService.php
├── HomepageSettingsService.php
└── HomepageDataResolver.php

Modules/CmsFrontend/resources/views/screens/{theme}/home/
├── layout.blade.php
└── blocks/
    ├── article-list.blade.php
    ├── notice.blade.php
    ├── documents.blade.php
    └── ...
```

Tên file có thể điều chỉnh theo convention thực tế của source, nhưng trách nhiệm của từng lớp phải giữ nguyên.

## 5. Cấu trúc database đề xuất

### 5.1. `homepage_block_instances`

Lưu các instance được tạo từ Definition:

```text
id
instance_key            unique
definition_key
name_vi
name_en                 nullable
is_system
created_at
updated_at
```

### 5.2. `homepage_theme_blocks`

Lưu bố cục theo theme:

```text
id
theme_key
block_instance_id
zone_key
variant_key             nullable
enabled
sort_order
created_at
updated_at

unique(theme_key, block_instance_id)
index(theme_key, zone_key, enabled, sort_order)
```

### 5.3. `homepage_block_settings`

Lưu cấu hình nội dung theo theme và instance:

```text
id
theme_key
block_instance_id
settings                JSON
created_at
updated_at

unique(theme_key, block_instance_id)
```

Không lưu PHP class, Blade path, JavaScript, CSS selector hoặc tên effect tùy ý trong database.

## 6. Developer Settings — Bố cục theo theme

Thêm tab `Bố cục trang chủ` trong Developer Settings.

Chức năng:

- Chọn theme cần cấu hình.
- Hiển thị danh sách zone do theme khai báo.
- Hiển thị những block instance theme hỗ trợ.
- Bật/tắt block.
- Kéo thả sắp xếp trong zone hợp lệ.
- Chuyển block giữa các zone được theme cho phép.
- Chọn variant trong danh sách variant theme hỗ trợ.
- Cảnh báo khi thiếu renderer, definition hoặc target quản lý.
- Khôi phục bố cục mặc định của theme.

Quy tắc:

- Chỉ phiên Developer đã mở khóa được ghi cấu hình này.
- Tắt block không xóa settings hoặc dữ liệu nghiệp vụ.
- Bật lại phải khôi phục đúng settings và vị trí đã lưu gần nhất nếu còn hợp lệ.
- Không cho bật block khi theme không có renderer tương ứng.
- Không cho kéo block vào zone không hỗ trợ.

### 6.1. Trang Review/Preview bố cục

Developer Settings phải có nút `Xem trước` để Developer kiểm tra toàn bộ trang chủ trước khi áp dụng cấu hình.

Mục tiêu:

- Xem đúng bố cục, thứ tự, variant và effect của theme đang cấu hình.
- Xem được cấu hình đang chỉnh nhưng chưa lưu hoặc chưa áp dụng.
- Không làm thay đổi trang chủ công khai trong lúc review.
- Không tạo một bản HTML giả hoặc giao diện preview riêng bị lệch với trang thật.

Luồng chuẩn:

```text
Developer chỉnh block/zone/thứ tự/variant
→ nhấn Xem trước
→ server validate toàn bộ cấu hình như lúc áp dụng thật
→ tạo preview snapshot ngắn hạn gắn với Developer session
→ mở trang Review bằng chính renderer, provider và asset của theme
→ Developer quay lại chỉnh hoặc nhấn Áp dụng
→ chỉ khi Áp dụng thành công thì cấu hình live mới thay đổi
```

Yêu cầu của trang Review:

- Hiện rõ nhãn `CHẾ ĐỘ XEM TRƯỚC`; không để người dùng nhầm với trang công khai.
- Hiện tên theme, thời điểm tạo snapshot và trạng thái `Chưa áp dụng`.
- Có nút quay lại cấu hình và nút `Áp dụng cấu hình` nếu phiên Developer còn hợp lệ.
- Cho xem nhanh theo kích thước Desktop, Tablet và Mobile.
- Có nút tải lại/replay để xem lại các effect khởi chạy khi block xuất hiện.
- Render dữ liệu thật theo Homepage Settings hiện có; nếu block chưa được chọn nguồn nội dung thì hiện trạng thái thiếu cấu hình rõ ràng, không làm trang lỗi.
- Block bị tắt trong snapshot phải không xuất hiện; block mới bật phải xuất hiện đúng zone và thứ tự đang review.
- Variant nào được chọn thì dùng đúng renderer và effect preset mà theme đã khai báo cho variant đó.
- Khuyến nông, Nam Bình Dương và Ban Đề án có thể review cùng một Definition bằng HTML và effect khác nhau.

Quy tắc kỹ thuật:

- Preview phải đi qua cùng `HomepageLayoutService`, `HomepageDataResolver` và renderer với trang chủ thật; chỉ thay nguồn layout live bằng preview snapshot.
- Không truyền toàn bộ cấu hình qua query string và không tin dữ liệu từ trình duyệt khi render.
- Snapshot được lưu phía server, gắn với Developer session bằng token ngẫu nhiên hoặc URL ký, có thời hạn ngắn và tự hết hạn.
- Người chưa mở khóa Developer không được xem snapshot; token của phiên này không dùng được ở phiên khác.
- Preview phải là read-only đối với dữ liệu nghiệp vụ và không ghi đè cấu hình live.
- Khi nhấn `Áp dụng`, server phải validate lại rồi cập nhật cấu hình trong transaction; không lấy kết quả render preview làm dữ liệu tin cậy.
- Preview không được index, cache công khai hoặc chia sẻ như URL trang thật.
- Không lưu JavaScript, CSS selector, Blade path hoặc tên effect tùy ý trong snapshot/database.
- Developer chỉ chọn variant/effect preset nằm trong allow-list do theme khai báo. Effect gắn với variant là mặc định ưu tiên để tránh tổ hợp không tương thích.
- Nếu renderer, asset hoặc effect preset bị thiếu, trang Review phải chỉ rõ block lỗi và không cho áp dụng cấu hình.

Trạng thái cấu hình:

- `Live`: cấu hình trang chủ công khai đang dùng.
- `Preview snapshot`: bản tạm theo phiên Developer, không phải một bản bố cục nghiệp vụ mới trong database.
- Giai đoạn đầu không cần hệ thống versioning nhiều bản. Lịch sử/rollback bố cục chỉ bổ sung khi có yêu cầu riêng.

## 7. Cài đặt trang chủ — form động

Trang `Cài đặt trang chủ` phải được sinh động từ:

1. Theme hiện tại.
2. Các block đang bật của theme.
3. `settingsSchema()` của từng Definition.

Ví dụ schema của block bài viết:

```php
[
    'category_id' => [
        'type' => 'category',
        'required' => true,
    ],
    'limit' => [
        'type' => 'number',
        'default' => 6,
        'min' => 1,
        'max' => 20,
    ],
    'sort' => [
        'type' => 'select',
        'options' => ['latest', 'manual'],
    ],
]
```

Các loại field ban đầu nên hỗ trợ:

- `category`
- `categories`
- `number`
- `text`
- `textarea`
- `boolean`
- `select`
- `management_link`

Quy tắc:

- Chỉ hiện settings của block đang bật.
- Block cần chuyên mục tự hiện ô chọn chuyên mục.
- Lưu ID chuyên mục, không lưu tên hoặc slug.
- Chuyên mục đã xóa phải hiện cảnh báo và yêu cầu chọn lại.
- Block có nơi quản lý riêng chỉ hiện mô tả và nút đi tới chức năng đó.
- Quản trị viên thường không được bật/tắt, đổi zone, variant hoặc sort order.
- Settings phải được validate phía server theo schema của Definition, không chỉ kiểm tra ở giao diện.

## 8. Render trang chủ

Luồng chuẩn:

```text
Xác định theme hiện tại
→ lấy Theme Layout và các zone
→ lấy block đang bật theo zone + sort_order
→ kiểm tra Definition và renderer
→ đọc/validate settings
→ Data Resolver lấy dữ liệu
→ render Blade đúng theme và variant
```

Theme chỉ khai báo các điểm render zone, ví dụ:

```blade
@renderHomepageZone('hero')
@renderHomepageZone('main')
@renderHomepageZone('sidebar')
@renderHomepageZone('after-main')
```

Không tiếp tục viết cứng từng `@include` block trong controller hoặc layout trang chủ sau khi block đó đã được migrate.

## 9. Hiệu ứng

Hiệu ứng không lưu trong database. Theme renderer quyết định effect bằng hook `data-*` và option hợp lệ.

Ví dụ:

```html
<section
    data-home-block="documents"
    data-effect="reveal stagger"
    data-effect-duration="900"
>
```

Quy tắc effect:

- Effect nằm trong thư viện `Modules/CmsFrontend/resources/assets/effects/`.
- Khởi tạo theo hook, không theo `nth-child` hoặc vị trí cố định trong DOM.
- Chỉ thao tác trong root block hiện tại.
- Block đổi thứ tự vẫn chạy đúng.
- Block tắt thì không có DOM và initializer phải tự no-op.
- Không khởi tạo hai lần.
- Không chứa tên theme trong package effect dùng chung.
- Theme được truyền option hợp lệ như duration, easing, autoplay.
- Effect có lifecycle `destroy()` nếu sau này hỗ trợ preview động.

Build single-theme phải đóng gói toàn bộ renderer/effect mà theme đó **có thể** bật. Vì vậy bật/tắt trong Developer Settings không cần build lại.

## 10. Thêm cụm mới trong tương lai

### 10.1. Chỉ khác nguồn nội dung

Nếu giao diện và cách lấy dữ liệu đã có, tạo Block Instance mới từ Definition hiện có. Không viết Definition, controller, Blade hoặc effect mới.

Ví dụ tạo `science-news` từ `article-list`, rồi chọn chuyên mục Khoa học.

### 10.2. Có nghiệp vụ hoặc giao diện mới

Cần bổ sung đúng một gói hoàn chỉnh:

1. Definition.
2. Lang VI/EN.
3. Data provider/resolver.
4. Renderer cho từng theme hỗ trợ.
5. CSS riêng của renderer nếu cần.
6. Effect package hoặc hook effect hiện có.
7. Khai báo theme/zone/variant hỗ trợ.
8. Build và deploy một lần.

Sau đó Developer có thể bật/tắt và sắp xếp mà không sửa code.

## 11. Hiệu năng và cache

- Cache layout đã resolve theo `theme_key`.
- Cache phải được xóa khi Developer đổi bố cục hoặc quản trị viên đổi settings.
- Không query Definition hoặc settings riêng lặp lại trong mỗi Blade.
- Data resolver phải hỗ trợ eager loading và tránh N+1.
- Nhiều instance cùng loại nên được gom truy vấn khi có thể.
- Chỉ render và khởi tạo effect của block đang bật.
- Cache key phải chứa theme và locale khi dữ liệu phụ thuộc ngôn ngữ.

## 12. Chuyển đổi từ hệ thống hiện tại

Thực hiện lần lượt để giảm rủi ro:

1. Xây Registry, schema DB và Developer UI nhưng chưa thay trang chủ hiện tại.
2. Khai báo zone và import bố cục mặc định của ba theme.
3. Tạo form động trong Cài đặt trang chủ, đọc được settings mới nhưng chưa xóa cấu hình cũ.
4. Chuyển từng block của Khuyến nông sang renderer động và đối chiếu giao diện.
5. Chuyển Nam Bình Dương.
6. Chuyển Ban Đề án.
7. Khi một block đã nghiệm thu, migrate cấu hình cũ và dừng đọc/ghi nguồn cũ của block đó.
8. Sau khi toàn bộ hoàn tất, xóa code cấu hình hard-code không còn dùng.

Trong quá trình chuyển đổi phải có legacy fallback theo từng theme/block, không bật hai renderer cho cùng một block.

## 13. Phạm vi không làm

- Không cho quản trị viên nhập JavaScript, PHP, Blade path hoặc CSS selector.
- Không cho tự tạo loại block hoàn toàn mới chỉ bằng một dòng database.
- Không gắn dữ liệu nghiệp vụ của khảo sát, liên hệ, bài viết... vào theme.
- Không biến Feature Registry thành Homepage Block Registry; đây là hai registry khác trách nhiệm.
- Không thay đổi controller/route của chức năng quản lý nội dung chỉ để phục vụ bố cục trang chủ.
- Không buộc các theme dùng cùng HTML hoặc cùng effect.

## 14. Tiêu chí hoàn thành

- Developer bật/tắt và sắp xếp block cho từng theme được.
- Developer xem trước cấu hình chưa áp dụng bằng đúng renderer, dữ liệu và effect thật của theme.
- Preview không làm thay đổi trang chủ live và người ngoài phiên Developer không truy cập được.
- Trang Review hỗ trợ Desktop, Tablet, Mobile và replay effect.
- Cấu hình thiếu renderer/asset/effect hợp lệ phải bị chặn trước khi áp dụng.
- Block chỉ được đặt vào zone hợp lệ.
- Khuyến nông có thể đặt Văn bản cạnh Thông báo.
- Ban Đề án có thể đặt Văn bản thành một cụm lớn riêng với effect khác.
- Cài đặt trang chủ tự sinh đúng field theo block đang bật.
- Quản trị viên thường không sửa được bố cục Developer.
- Tắt block không mất settings; bật lại khôi phục được.
- Theme thiếu renderer không thể bật block.
- Đổi bố cục không cần build lại.
- Thêm instance từ Definition có sẵn không cần sửa controller/Blade.
- Thêm loại block mới chỉ thay đổi tại registry/provider/renderer/effect liên quan.
- Không phát sinh N+1 rõ ràng và không tải effect không thuộc profile theme.
- Ba theme giữ nguyên nhận diện, responsive và hành vi hiện tại sau khi migrate.

## 15. Quyết định đã chốt

- Tên chức năng: **Homepage Layout Manager — Quản lý bố cục trang chủ**.
- Bố cục lưu theo `theme_key` vì đây là cấu hình trình bày.
- Dữ liệu nghiệp vụ không gắn theme.
- Dùng `zone`, không dùng một danh sách block phẳng cho toàn trang.
- Dùng Definition + Instance để tái sử dụng loại block.
- Cài đặt trang chủ sinh form động từ schema.
- Effect do renderer/theme quyết định, không lưu code effect trong DB.
- Developer review variant/effect bằng preview snapshot theo phiên; chỉ nút `Áp dụng` mới thay đổi cấu hình live.
- Developer quản lý bố cục; quản trị viên quản lý nguồn nội dung.

