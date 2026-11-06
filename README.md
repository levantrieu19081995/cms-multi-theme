# CMS — ghi chú định hướng và triển khai

Cập nhật: 21/10/2026. Tài liệu ghi lại các quyết định đã thống nhất để tiếp tục đúng luồng. Các mục **đã hoàn thành** và **định hướng chưa triển khai** được phân biệt bên dưới.

## 1. Mục tiêu

Phát triển CMS dùng được cho nhiều đơn vị, với Nam Bình Dương là đơn vị triển khai đầu tiên trên bản source độc lập này.

- Chức năng, menu và vai trò đã có cơ chế quản lý; tiếp tục sử dụng cơ chế hiện tại.
- Tổ chức lại để mỗi chức năng có một nguồn xử lý chính, tránh sao chép controller/service cho từng đơn vị.
- Chỉ chọn theme cho toàn bộ trang công khai tại Cài đặt chung; quản trị dùng cố định giao diện hiện tại.
- Logo, tên đơn vị, thông tin liên hệ và dữ liệu thuộc cấu hình đơn vị. Bố cục, component và cách trình bày thuộc theme.
- Đổi theme không đổi database, quyền, menu, trạng thái bài hoặc luồng nghiệp vụ.

## 2. Nguồn kế thừa và phạm vi

- **Sóc Trăng:** nguồn đối chiếu nghiệp vụ trường học, gồm các chức năng cần đưa lại như tuyển sinh. Danh sách chức năng cụ thể phải được rà soát trước khi chuyển.
- **Khuyến nông:** nền ban đầu của source mới, với giao diện quản trị đã thay đổi. Bản này đã cắt một số chức năng trường học so với Sóc Trăng.
- **Nam Bình Dương:** dùng các chức năng phù hợp trường học; có thể dùng bố cục quản trị hiện tại với nhận diện riêng và một theme công khai riêng.

Không tạo thêm cặp module đầy đủ cho mỗi khách hàng rồi sao chép toàn bộ chức năng. Phần khác biệt về giao diện được đặt trong theme; chỉ bổ sung xử lý riêng khi nghiệp vụ thực sự khác.

Source Khuyến nông gốc nằm tại `D:\LEVANTRIEU\FileInstall\Laragon\laragon\www\CMS_Khung`. Những thay đổi cho CMS mới không được tự động áp dụng ngược vào source hoặc database của Khuyến nông/Sóc Trăng.

## 3. Đã hoàn thành

- Source độc lập hiện tại: `D:\DuAn\CMS`.
- Hai module đang bật: `CmsBackend` và `CmsFrontend`; tên kỹ thuật trung lập, không đặt theo đơn vị.
- Database riêng: `cms_workspace`. Đơn vị mặc định trong cấu hình/dữ liệu: Nam Bình Dương.
- Upload, cache/session và bản build được tách khỏi bản Khuyến nông gốc. Liên kết `public/storage` phải trỏ vào chính source này.
- Đã loại 4.876 bài tin cũ khỏi database mới, giữ 58 bài tin và 37 nội dung khác phục vụ website. Đây là số liệu tại thời điểm dọn, không phải giới hạn dữ liệu của hệ thống.
- Media sau khi dọn khoảng 108 MB; toàn source khoảng 1,14 GB, gồm cả thư viện phát triển. Dung lượng sẽ thay đổi khi thêm nội dung/build.
- Quản trị tin tức/sự kiện có danh sách **Đã xóa** và thao tác **Xóa vĩnh viễn kèm tập tin**.
- Đã chạy đạt 18 kiểm thử liên quan tại thời điểm hoàn tất đợt dọn dữ liệu; chưa nghiệm thu toàn bộ CRUD/chức năng của hệ thống.

Bản sao lưu trước khi dọn: `D:\DuAn\CMS-backups\cleanup-2026-10-21-01`.

Bản sao lưu nằm ngoài source nhưng vẫn chiếm dung lượng ổ đĩa; không coi dung lượng đã đưa ra ngoài source là dung lượng đã giải phóng khỏi ổ đĩa.

## Quy ước URL chuẩn (đã triển khai ngày 30/10/2026)

Mỗi khách hàng production có **một domain, một database và một theme công khai riêng**. Domain đã xác định đơn vị nên URL không chứa `project_key` hoặc `theme_key`; đổi theme không được làm thay đổi đường dẫn. Toàn bộ chức năng quản trị dùng chung tiền tố ổn định `/cms`, còn website công khai nằm ở gốc domain.

```text
Website công khai
├── /
├── /tin-tuc
├── /gioi-thieu
└── /su-kien

Quản trị
├── /cms
├── /cms/bai-viet
├── /cms/marquee
├── /cms/binh-luan
├── /cms/truyen-thong
└── /cms/developer-settings
```

Quy tắc áp dụng khi chuẩn hóa route:

- `/cms` là ranh giới duy nhất giữa website công khai và quản trị; không đưa chức năng quản trị ra thẳng gốc như `/marquee`.
- Loại hai tầng kỹ thuật dư thừa `/dashboard/cdn` khỏi URL canonical mới. Tên route nội bộ nên được giữ nguyên khi có thể để giảm ảnh hưởng controller, phân quyền, breadcrumb và form.
- Các đường dẫn cũ như `/cms/dashboard/cdn/marquee`, `/binh-luan` hoặc `/cms/dashboard/truyen-thong` phải được redirect sang URL mới trong giai đoạn chuyển tiếp; chưa xóa route cũ ngay.
- Tất cả menu, breadcrumb, redirect sau khi lưu, form action và JavaScript phải sinh URL từ **tên route**, không ghép chuỗi hoặc viết cứng tiền tố.
- Route quản trị mới vẫn phải đi qua middleware đăng nhập và kiểm tra quyền hiện có. URL ngắn hơn không thay thế kiểm soát truy cập.
- OAuth Facebook của Nam Bình Dương sau khi chuẩn hóa dùng callback canonical `https://nambinhduong.cusc.vn/cms/truyen-thong/tai-khoan/facebook/callback`; chỉ đổi trên Meta sau khi route mới đã triển khai và redirect cũ đã sẵn sàng.
- Khi chạy nhiều dự án bằng IP để demo, reverse proxy có thể thêm tiền tố triển khai bên ngoài ứng dụng (`/nam-binh-duong`, `/ban-de-an`), nhưng route bên trong mỗi dự án vẫn theo cấu trúc `/cms/...` trên domain chính thức.

**Trạng thái:** đã triển khai trong source. Website công khai nằm ở gốc domain (`/`, `/tin-tuc`, `/su-kien`, `/en/...`), toàn bộ quản trị nằm dưới `/cms`; tên route (`cms.*`, `cms.dashboard.*`, `cms.dashboard.cdn.*`, `users.*`, `roles.*`, ...) giữ nguyên, chỉ đổi URI.

- Public: `Modules/CmsFrontend/routes/public.php` (tiền tố công khai `cmsbackend.public_route_prefix` = rỗng). Quản trị: `Modules/CmsBackend/routes/web.php`, `Modules/CmsFrontend/routes/web.php` (đăng nhập, tổng quan `/cms`, `/cms/setting/trang-chu`) và `routes/web.php` (người dùng, vai trò, menu, bình luận, cài đặt chung... đều `/cms/...`). Tiền tố quản trị lấy từ `CMS_ROUTE_PREFIX` (mặc định `cms`).
- Menu quản trị sinh liên kết từ **tên route** nằm ở `slug` của từng mục (không còn ghép đường dẫn). Script quản trị nhận endpoint qua `window.cmsUrls` do server sinh từ tên route.
- Địa chỉ cũ chỉ là **redirect tạm thời 302, chỉ GET** (chưa chuyển 301): `/cms/dashboard[/cdn]/x` → `/cms/x`; `/cms/tin-tuc`, `/cms/x.htm`, `/cms/en/...`, `/cms/sitemap.xml`, `/cms/thu-vien-video` → gốc site; `/binh-luan`, `/users`, `/roles`, `/menus`, `/setting/general`... → `/cms/...`; `/dashboard` → `/cms`. Request ghi dữ liệu (POST/PUT/PATCH/DELETE) không được redirect: form và JavaScript gửi thẳng tới địa chỉ mới. Các trang cũ `/bai-viet`, `/trang`, `/banner`, `/lien-ket` (đã được module thay thế) vẫn đăng ký tại địa chỉ cũ chỉ như alias tạm, trang GET chuyển sang địa chỉ mới.
- **Facebook OAuth:** callback chuẩn là `https://<domain>/cms/truyen-thong/tai-khoan/facebook/callback`. Cập nhật `META_REDIRECT_URI` trong `.env` và mục *Facebook Login → Settings → Valid OAuth Redirect URIs* trên Meta Developer; callback cũ `/cms/dashboard/truyen-thong/tai-khoan/facebook/callback` vẫn hoạt động trong giai đoạn chuyển tiếp nhờ redirect GET giữ nguyên query (`code`, `state`); hệ thống luôn tạo yêu cầu OAuth bằng `META_REDIRECT_URI`.

### Quy tắc xóa hiện có

- **Xóa:** đánh dấu xóa, giữ dữ liệu và file.
- **Xóa vĩnh viễn kèm tập tin:** dành cho vai trò quản trị, đồng thời kiểm tra quyền xóa và phiên bản bài.
- Bài đang đăng phải ngừng đăng trước khi xóa vĩnh viễn; bài đã đánh dấu xóa có thể xử lý từ danh sách Đã xóa.
- File còn được nội dung khác sử dụng phải được giữ lại. Không xóa nguyên thư mục chỉ dựa vào ID bài.
- Có kiểm tra các liên kết menu/banner/bài con trước khi xóa vĩnh viễn.

## 4. Kiến trúc multi-theme — đã triển khai nền và theme mặc định

Đã chuyển 159 view của hai module vào `screens/default` (90 BE, 69 FE), giữ giao diện hiện tại làm theme mặc định. Header/sidebar quản trị đã tách thành component. Các tên view cũ có ánh xạ tập trung để giữ tương thích trong giai đoạn chuyển tiếp.

Luồng xử lý đích:

```text
Route
  → Controller dùng chung
  → Service lấy dữ liệu / xử lý nghiệp vụ
  → Xác định theme được chọn
  → Screen của theme
  → Layout + component hiển thị
```

- Controller nhận yêu cầu, phối hợp xử lý và trả kết quả.
- Service chứa nghiệp vụ và việc chuẩn bị dữ liệu.
- Screen/component nhận dữ liệu thống nhất; không tự truy vấn database hoặc quyết định quyền/nghiệp vụ.
- Theme không có bản controller/service riêng chỉ để đổi bố cục.
- Không cần tách thành một module hay cây thư mục lớn cho từng chức năng.
- Triển khai có thể chuyển lần lượt từng chức năng để dễ kiểm chứng, nhưng chúng cùng tuân theo cơ chế chọn theme của đơn vị.

### Cấu trúc đích dự kiến

Các thư mục `default` bên dưới đã có trong source. Theme Nam Bình Dương và Ban Đề án đã được tạo, đăng ký riêng cho trang công khai. Controller vẫn ở vị trí chuẩn Laravel.

```text
Modules/
├── CmsBackend/
│   ├── app/
│   │   ├── Http/Controllers/
│   │   └── Services/
│   └── resources/views/screens/
│       ├── shared/                 # Component thực sự dùng chung giữa các theme
│       └── default/
│       │   ├── layout.blade.php
│       │   ├── components/         # Header, sidebar, bảng, trường nhập...
│       │   └── pages/              # Các màn hình quản trị
└── CmsFrontend/
    ├── app/
    │   ├── Http/Controllers/
    │   └── Services/
    └── resources/views/screens/
        ├── shared/
        ├── default/
        │   ├── layout.blade.php
        │   ├── home.blade.php
        │   ├── components/         # Tin nổi bật, sự kiện, album...
        │   └── pages/
        ├── nam-binh-duong/
            ├── layout.blade.php
            ├── home.blade.php
            ├── components/
            └── pages/
        └── ban-de-an/
            ├── layout.blade.php
            ├── home.blade.php
            ├── components/
            └── pages/
```

CSS/JS chính của theme mặc định đã tách vào `resources/assets/css/screens/default/` và `resources/assets/js/screens/default/` trong module tương ứng. Các tài nguyên chức năng dùng chung vẫn giữ ở vị trí cũ. Entry build của CmsFrontend nay lấy từ registry `scripts/theme-profiles.mjs` (shared + các theme của profile), không còn quét mọi thư mục `screens/` một cách tự động; xem mục 8. Không cần sao chép cấu hình Vite cho từng theme, nhưng theme mới phải được khai báo trong registry.

### Cách chọn giao diện

Chỉ giữ **Cài đặt chung → Giao diện trang công khai** để chọn theme cho toàn bộ website. Quản trị dùng cố định `screens/default`; không phát triển hoặc chọn theme quản trị.

Đã bỏ lựa chọn **Báo chí / Hiện đại** khỏi Cài đặt trang chủ và ngừng đọc/ghi key cũ `cms_home_theme`. Dữ liệu key cũ được giữ lại để tương thích sao lưu nhưng không còn điều khiển giao diện. Theme mặc định dùng thư viện ảnh dạng lưới đang hiển thị; theme công khai mới tự định nghĩa component theo thiết kế. Cài đặt trang chủ chỉ quản lý nội dung các khối.

Hiện có ba theme công khai: `default`, `nam-binh-duong` (SBUT) và `ban-de-an`. Database vẫn giữ lựa chọn hiện tại; chỉ đổi theme khi người quản trị lưu tại Cài đặt chung.

Theme nào thiếu phần ghi đè có thể dùng phần mặc định theo quy tắc fallback rõ ràng. Cần kiểm tra sự tương thích dữ liệu/layout; không tự fallback sang một giao diện không phù hợp chỉ để che lỗi.

## 5. Tiến độ và bước tiếp theo

- [x] Rà và chuyển view của hai module vào theme mặc định.
- [x] Chỉ chọn theme công khai; quản trị cố định default, bỏ bộ chọn Báo chí/Hiện đại cũ.
- [x] Layout/component chung và luồng tin tức sử dụng screen resolver, cùng controller/service hiện có.
- [x] Chuyển chuẩn bị dữ liệu và cache nội dung trang chủ từ Blade sang HomePresentation; cache phân biệt theme, đơn vị/database và ngôn ngữ.
- [x] Chuẩn bị quyền thao tác tin/sự kiện tại controller; hạn chế query lặp menu/chuyên mục/thương hiệu theo request.
- [x] Kiểm thử đổi theme, lưu cài đặt/quyền, các trang công khai và quy trình tin tạo → gửi duyệt → duyệt → đăng → ngừng đăng → xóa → xóa vĩnh viễn. Bản ghi thử được rollback. Đợt này chạy đạt 35 kiểm thử liên quan (1.907 assertions), kiểm tra cú pháp 412 file PHP và build thành công; chưa chạy toàn bộ bộ kiểm thử cũ.
- [ ] Rà danh sách chức năng trường học bị thiếu so với Sóc Trăng và đưa lại từng chức năng, bắt đầu với tuyển sinh theo phạm vi được thống nhất.
- [x] Tạo, đăng ký và chọn theme công khai Nam Bình Dương (SBUT), có các cụm chức năng hiện hữu.
- [x] Bổ sung theme công khai Ban Đề án theo bố cục đã duyệt; giữ chung controller, route, quyền và dữ liệu CMS.
- Quản trị và các màn hình core giữ giao diện hiện tại; chỉ mở rộng theme ở CmsFrontend.

### Vị trí code chính

| Phần | Vị trí |
| --- | --- |
| Đăng ký theme và ánh xạ tên view cũ | Modules/CmsBackend/config/screens.php |
| Chọn theme công khai, fallback, cố định quản trị | Modules/CmsBackend/app/Services/ScreenTheme.php |
| Nối với view finder của Laravel | Modules/CmsBackend/app/Support/ScreenViewFinder.php |
| Kiểm tra quyền và dữ liệu cài đặt | Modules/CmsBackend/app/Http/Requests/UpdateScreenThemesRequest.php |
| Lưu cài đặt theme | Modules/CmsBackend/app/Http/Controllers/ScreenThemeController.php |
| Chuẩn bị/cache nội dung trang chủ | Modules/CmsFrontend/app/Services/HomePresentation.php |
| Quản trị tin/sự kiện | CmsBackend/resources/views/screens/default/pages/news và pages/events |
| Layout và component quản trị | CmsBackend/resources/views/screens/default/layout.blade.php và components/ |
| Trang chủ công khai | CmsFrontend/resources/views/screens/default/home.blade.php |

Các đường dẫn bắt đầu bằng CmsBackend/CmsFrontend trong bảng đều nằm dưới Modules/. Thư mục screens/shared ở sơ đồ là vị trí dành cho component thực sự dùng giữa nhiều theme; chưa tạo bản sao ở đó khi chỉ có default.

Các thân màn hình dùng chung còn nằm trong resources/views hoặc theme Materialize được giữ làm fallback hiện hữu. Khi cần ghi đè một view core, có thể cung cấp screen tương ứng trong pages/core/<tên view với dấu chấm đổi thành thư mục>. Những màn hình tương thích đã có trong module (cài đặt, file, video, layout...) dùng ánh xạ shared_views trong config/screens.php. Không đổi controller chung chỉ để đổi cách hiển thị.

### Quy ước thêm theme sau này

1. Đăng ký key, label và surfaces (chỉ public) trong config/screens.php; chỉ dùng key chữ thường, số và dấu gạch ngang.
2. Tạo thư mục resources/views/screens/<key>/ của module tương ứng. Chỉ tạo screen/component cần khác; phần thiếu dùng default cùng hợp đồng dữ liệu.
3. Gọi view trung lập, ví dụ cmsfrontend::screens.home hoặc cmsbackend::screens.pages.news.index. Có thể dùng ScreenTheme::view(surface, screen). Không hardcode key của đơn vị vào controller.
4. Đặt tài nguyên riêng trong resources/assets/css/screens/<key>/ và js/screens/<key>/; khai báo @vite trong screen/component phù hợp, **khai báo entry và dependency của theme trong scripts/theme-profiles.mjs** (mục 8), rồi chạy build trước khi triển khai.
5. Kiểm thử fallback, cache, quyền và thao tác trước khi chọn theme trong cài đặt. Đổi theme không bật/tắt chức năng hoặc chuyển database.

Mỗi request chỉ đọc lựa chọn theme công khai một lần; bỏ qua option theme quản trị cũ. Không chọn theme bằng query parameter. Nếu key lưu trong DB không còn được đăng ký cho đúng bề mặt, hệ thống dùng default. Ở môi trường debug, fragment trang chủ không cache để việc sửa view hiện ngay; môi trường triển khai dùng key cache có chứa theme/version và dấu dữ liệu nội dung.

Bản sao lưu mã trước khi tách theme: D:\DuAn\CMS-backups\theme-default-2026-10-21-01\source-before.zip. Danh sách chuyển view nằm cùng thư mục trong view-migration.json.

## 6. Nguyên tắc tốc độ

Chia thư mục/component không phải yếu tố chính quyết định tốc độ trang chủ. Cần tập trung vào truy vấn, cache và tài nguyên tải về.

- Xác định theme một lần trong mỗi request; không truy vấn cấu hình lại ở từng component.
- Không query trong Blade/component. Chuẩn bị dữ liệu theo khối ở controller/service.
- Giới hạn số bài từng khối; tải sẵn các quan hệ cần dùng và tránh truy vấn lặp/N+1.
- Cache dữ liệu phù hợp và có quy tắc làm mới khi nội dung/cấu hình thay đổi. Không dùng chung cache nội dung cá nhân, nội bộ hoặc khác quyền truy cập.
- Giữ điều kiện công khai, trạng thái duyệt, lịch đăng và ngày hết hạn cả khi lấy dữ liệu lẫn khi cache.
- Dùng ảnh đúng kích thước; tải trễ ảnh phía dưới, hạn chế video tự tải và phân trang danh sách.
- Dùng bản build cùng cache Blade/config/route phù hợp khi triển khai; kiểm tra route-cache thực tế trước khi áp dụng.
- Đo số truy vấn, thời gian phản hồi và dung lượng tải trước/sau. Không hứa tốc độ chỉ từ việc đổi cấu trúc thư mục.

## 7. Chạy local

Máy cần có PHP 8.2+, Composer và Node.js trong `PATH`. Chuyển vào source rồi dùng một lệnh để chạy đồng thời Laravel và Vite:

```bat
cd /d D:\DuAn\CMS\cms-multi-theme
npm run dev:all
```

Giữ Terminal mở; nhấn `Ctrl+C` để dừng cả Vite và Laravel server. Địa chỉ cấu hình local hiện tại: website `http://127.0.0.1:8011/`, quản trị `http://127.0.0.1:8011/cms`; đăng nhập tại `/cms/login`.

Nếu muốn chạy riêng từng tiến trình để kiểm tra lỗi, dùng hai Terminal:

```bat
php artisan serve --host=127.0.0.1 --port=8011
npm run dev
```

`npm run dev` chỉ cung cấp tài nguyên phát triển, không thay thế tiến trình Laravel. Chạy theo từng theme hoặc build riêng từng theme: xem mục 8. Khi dùng bản build tĩnh, dừng Vite rồi chạy:

```bat
npm run build
```

Nếu chuyển thư mục source, kiểm tra `.env`, làm mới cấu hình và tạo lại liên kết upload trong chính source mới:

```bat
php artisan optimize:clear
php artisan storage:unlink
php artisan storage:link
```

Không chạy migration/seeder hoặc thay đổi database chỉ để khởi động bản source đã có dữ liệu.

### Điều chỉnh ngày 21/10/2026: chỉ dùng theme công khai

Đã bỏ bộ chọn theme quản trị và bộ chọn Báo chí/Hiện đại ở Cài đặt trang chủ. Cài đặt chung chỉ lưu `cms_public_theme`; request gửi lựa chọn quản trị bị từ chối, option quản trị cũ bị bỏ qua. Đã chạy đạt 35 kiểm thử liên quan; chưa chạy toàn bộ test suite.

## 8. Dev/Build theo từng theme (asset profile)

Mục này ghi nhận cơ chế chọn asset khi chạy dev và build. Nó chỉ quyết định **CSS/JavaScript nào được build, theo dõi và cho phép chọn**; không đổi nghiệp vụ, route, quyền, menu hay dữ liệu.

> **Cảnh báo về dữ liệu.** Cơ chế build theo theme chỉ tách CSS, JavaScript và giao diện. Nó **không** tự tách dữ liệu database. Xem mục 8.8 trước khi triển khai riêng cho một dự án.

### 8.1. Cấu trúc multi-theme hiện tại

| Theme | Đơn vị | Source asset riêng |
| --- | --- | --- |
| `default` | Khuyến nông | `css/screens/default/{shell,home}.css`, `js/screens/default/shell.js` |
| `nam-binh-duong` | Nam Bình Dương | `css/screens/nam-binh-duong/theme.css`, `js/screens/nam-binh-duong/theme.js` |
| `ban-de-an` | Ban Đề án | `css/screens/ban-de-an/theme.css`, `js/screens/ban-de-an/theme.js` |

- Source của mỗi theme vẫn nằm trong `Modules/CmsFrontend/resources/assets/{css,js}/screens/<theme>/`. Không di chuyển hay đổi tên thư mục.
- **Shared asset** là mọi file CSS/JS/SCSS của CmsFrontend nằm ngoài `screens/**` (hiện 34 file: đăng nhập/đăng ký, listing, banner, các trang chức năng...). Luôn được build, kèm CSS/JS đăng nhập chạy sớm của `effects/auth` (xem bên dưới).
- **Core** (`resources/assets/**` và gói theme Materialize ở `resources/themes/materialize`) và **CmsBackend** luôn được build ở mọi profile; không bao giờ bị lọc.
- **Thư viện effect** `Modules/CmsFrontend/resources/assets/effects/` (danh mục: `effects/EFFECTS.md`) chứa chuyển động dùng lại; theme chỉ import và gọi. CSS effect khai báo trong `sharedEntries` của từng theme ở `scripts/theme-profiles.mjs`: `default` = reveal, stagger, gallery-orbit, crossfade-slider (JS gọi từ `js/screens/default/home-effects.js`, chỉ nạp ở trang chủ); `nam-binh-duong` và `ban-de-an` = `shared/campus-base.css`, reveal, stagger (JS gọi từ `theme.js`); `effects/auth/*` dùng chung mọi profile. Chế độ `all` gộp tất cả, đã loại trùng. `js/scroll-reveal.js` và `css/scroll-reveal.css` cũ đã bị xóa.
- **Ban Đề án không còn phụ thuộc Nam Bình Dương**: layout tải `shared/campus-base.css` và `theme.css`/`theme.js` của chính nó.
- **Fallback sang `default`**: Nam Bình Dương và Ban Đề án không có view `layouts/sections/stylesFront` và `scriptsFront` riêng, nên `ScreenTheme` dùng bản của `default`, bản này tải `screens/default/shell.css` và `shell.js`. Vì vậy hai file này được khai báo là `fallbackEntries` và luôn đi kèm hai theme đó. Không được bỏ chúng khỏi bản build.

### 8.2. Cấu trúc file

```text
scripts/
├── theme-profiles.mjs   # Registry duy nhất: theme, dependency, entry, fallbackEntries, unusedViews
├── theme-build.mjs      # Build từng theme hoặc toàn bộ theme, kiểm tra Blade/manifest, ghi metadata
├── theme-dev.mjs        # Chạy Laravel + Vite theo profile (gọi dev-all.ps1)
├── dev-all.ps1          # Khởi động và dừng chung Laravel/Vite (nhận -Mode, -Theme)
├── project-build.mjs    # Build theo project CMS/Khung (build:cms, build:khung)
└── project-profiles.mjs # Danh sách module của từng project

Modules/CmsBackend/
├── app/Support/AssetProfile.php                       # Đọc profile lúc chạy (env / hot / theme-build.json)
├── app/Services/ScreenTheme.php                       # Chỉ cho chọn theme đã build; fallback không sửa DB
├── app/Http/Controllers/ScreenThemeController.php    # Từ chối lưu theme khi bản build bị khóa
└── config/screens.php                                 # Đăng ký theme + cấu hình assets.mode/theme từ env

vite.config.js                       # Đọc profile; ở chế độ single không theo dõi theme khác
Modules/CmsFrontend/vite.config.js   # Entry = shared + theme của profile (lấy từ registry)

build-exports/                       # Thư mục xuất bản build; public/build không bị ghi
├── all-themes/build/                # build:all-themes
├── themes/
│   ├── default/build/               # build:default
│   ├── nam-binh-duong/build/        # build:nbd
│   └── ban-de-an/build/             # build:bda
└── cms/build/                       # build:cms (đường dẫn cũ, vẫn giữ)
```

Mọi theme chỉ được khai báo trong `scripts/theme-profiles.mjs`; không khai báo ở nhiều file khác nhau. Việc đăng ký theme hiển thị cho người quản trị (nhãn, nội dung, `version`) vẫn nằm ở `config/screens.php`.

### 8.3. Hai chế độ chạy

Chọn bằng biến môi trường **của tiến trình** (không sửa `.env`): `CMS_ASSET_MODE=all|single` và `CMS_ASSET_THEME=<key>`. Không đặt biến nào = `all` (giữ nguyên hành vi trước đây).

**All-themes**
- Build/watch asset của tất cả theme.
- Người quản trị được đổi theme ở Cài đặt chung như trước.
- Phù hợp phát triển khung và bản demo chung.

**Single-theme**
- Chỉ build shared asset, theme được chọn và dependency của nó (kèm `fallbackEntries`). Không build asset của theme không liên quan. Khi chạy dev, Vite không theo dõi thư mục các theme còn lại.
- Cài đặt chung khóa chức năng đổi theme: ô chọn bị vô hiệu, ẩn nút lưu và hiện ghi chú "Bản triển khai này được cố định giao diện …"; yêu cầu lưu bị từ chối.
- Database không bị sửa.
- Nếu database đang lưu theme khác, runtime dùng theme đã build, ghi cảnh báo vào log (một lần mỗi request) và **không** tự cập nhật option `cms_public_theme`.

### 8.4. Danh sách lệnh

```powershell
# Chạy Vite riêng, KHÔNG chạy Laravel (tương thích như trước; không có profile = all-themes)
npm run dev

# Chạy Laravel và Vite với tất cả theme
npm run dev:all
npm run dev:all-themes

# Chạy Laravel và Vite theo một theme
npm run dev:default
npm run dev:nbd
npm run dev:bda
npm run dev:theme -- nam-binh-duong

# Chạy Laravel và Vite theo dự án (CMS_PROJECT_KEY + giao diện): xem mục 8.11
npm run start:nbd
npm run start:bda
npm run start:kn
npm run start:all

# Build tất cả theme
npm run build:all-themes
npm run build:cms

# Build riêng từng theme
npm run build:default
npm run build:nbd
npm run build:bda
npm run build:theme -- ban-de-an

# Không đổi
npm run build        # vite build toàn bộ module đang bật
npm run build:khung  # build khung, không đổi hành vi
```

- **`npm run dev` chỉ chạy Vite.** Muốn chạy cả PHP và Vite phải dùng `dev:all`, `dev:all-themes` hoặc `dev:<theme>`.
- Mỗi lần chỉ chạy một theme từ một thư mục source (ví dụ `dev:nbd`, dừng, rồi `dev:bda`). Không tạo nhiều `public/hot` cạnh tranh nhau.
- `Ctrl+C` dừng cả Laravel và Vite (qua `dev-all.ps1`).
- `build:cms` giữ nguyên đường dẫn và hành vi cũ (tương đương `build:all-themes`, cũng ghi `theme-build.json` với `mode: all`); dùng làm đường lui khi profile riêng có vấn đề.
- Kiểm tra Blade/registry mà không build: `node scripts/theme-build.mjs <all|theme> --check-only`.

### 8.5. Vị trí output và cách áp dụng

- Lệnh build **không tự ghi vào `public/build`** và không xóa bản đang chạy.
- Build riêng theme: `build-exports/themes/<theme>/build`. Build tất cả: `build-exports/all-themes/build`. `build:cms`: `build-exports/cms/build`. Mỗi profile ghi vào thư mục riêng, không xóa output của profile khác.
- Để áp dụng/triển khai, đưa nội dung của thư mục build phù hợp vào `public/build` của **dự án đích**.
- Phải chép đủ `manifest.json`, `theme-build.json` và thư mục `assets`.
- Không trộn file của hai profile build khác nhau trong cùng `public/build`.
- Trước khi thay `public/build`, sao lưu bản đang chạy.

Sau mỗi lần build, script kiểm tra: có `manifest.json`; có asset Core, CmsBackend và CmsFrontend; có đủ entry của shared và theme được chọn (kể cả dependency); ở chế độ single không lẫn entry theme khác ngoài `fallbackEntries`; mọi file được manifest tham chiếu đều tồn tại; và mọi `@vite()` mà view của theme có thể dùng (gồm cả view `default` mà theme fallback sang) đều có trong tập entry của profile. Thiếu asset thì lệnh build thất bại, không báo thành công.

#### Deploy riêng giao diện Khuyến nông lên 228

```powershell
# Kiểm tra trước, không build và không kết nối server
npm run deploy:ui:kn -- -DryRun

# Build theme default và deploy giao diện
npm run deploy:ui:kn

# Dùng lại bản build default hiện có
npm run deploy:ui:kn -- -SkipBuild

# Sau khi sao lưu database, chạy toàn bộ migration đang Pending
npm run deploy:ui:kn -- -RunMigrations
```

Script `scripts/deploy-ui-228.ps1` cố định đích `container-008-knct` tại `/domain/khuyennong`. Script chỉ chuyển Blade view, hai lớp trình bày mà giao diện gọi trực tiếp (`LocaleManager`, `AuthPageContext`), bản dịch, tài nguyên tĩnh và bản `public/build` của theme `default`; có sao lưu giao diện đang chạy trước khi thay thế. Script không chép `public/hot`, không sửa `.env`, `storage`, dữ liệu upload, Nginx, domain hoặc SSL.

Mặc định script **không chạy** migration, seeder, Composer hoặc thao tác database. Khi truyền rõ `-RunMigrations`, script chép thư mục migration, sao lưu đầy đủ database vào `/domain/backups/db-before-migrate-<thời-gian>.sql.gz`, xác định migration mới hơn migration cuối đã ghi nhận, tạm cô lập đúng các file mới và đồng thời cô lập migration cũ của CmsBackend/CmsFrontend, rồi chạy `php artisan migrate --force` bằng `www-data`. Cách cô lập này tránh chạy lại các migration nền đang báo Pending trong database Khuyến nông được nhập thủ công. Nếu không xác định được migration mốc, script từ chối chạy. Seeder không bao giờ tự chạy. Mọi lệnh Artisan trong container phải chạy bằng `www-data`, không chạy bằng `root`.

### 8.6. Metadata `theme-build.json`

Mỗi bản build ghi file này cạnh `manifest.json`; Laravel đọc nó từ `public/build/theme-build.json` để biết theme nào khả dụng (không chỉ dựa vào `.env`, vì `.env` có thể sai so với bộ asset thực tế).

```json
{ "mode": "all", "themes": ["default", "nam-binh-duong", "ban-de-an"] }
{ "mode": "single", "themes": ["nam-binh-duong"] }
{ "mode": "single", "themes": ["ban-de-an"], "dependencies": ["nam-binh-duong"] }
```

- `mode: all`: bản build chứa tất cả theme.
- `mode: single`: bản build chỉ cho phép một theme.
- `themes`: danh sách theme được phép chọn.
- `dependencies`: dependency asset của single theme (nếu có).
- Không có file metadata = coi như `all` (hành vi cũ).

Thứ tự `AssetProfile` xác định profile lúc chạy: (1) biến môi trường `CMS_ASSET_MODE`/`CMS_ASSET_THEME`; (2) có `public/hot` thì coi là Vite dev (`all`), để file metadata cũ không khóa phiên dev; (3) `public/build/theme-build.json`; (4) không có gì thì `all`.

Lưu ý khi vận hành:
- **Không được để sót `public/hot` trên production.** Nếu còn, runtime có thể nhận diện nhầm là môi trường Vite dev và bỏ qua `theme-build.json`.
- Sau khi đổi biến profile, hoặc trước khi kiểm tra local, chạy `php artisan config:clear` (biến env đi vào `config/screens.php`; khi config đã cache thì giá trị cũ vẫn được dùng).

```powershell
php artisan config:clear
```

### 8.7. Quy tắc code theo theme

- Mỗi theme import thư viện riêng trong entry của nó; không import thư viện riêng theme vào asset dùng chung (`app.js`, `shell.js`, CmsBackend, shared entry). Thư viện nặng chỉ dùng ở một chức năng nên dùng `import()` động.
- CSS theme nên nằm dưới root class của theme; animation/biến global dùng tiền tố riêng. JavaScript theme chỉ khởi tạo khi tìm thấy root của chính nó. *Hiện chưa chuẩn hóa hoàn toàn: ví dụ theme Nam Bình Dương dùng root `.sbut-public`.*
- Thêm theme mới: ngoài các bước ở mục 5, **phải khai báo entry và dependency trong `scripts/theme-profiles.mjs`**. Entry của theme không còn được lấy tự động từ glob.

### 8.8. Quan hệ giữa asset và dữ liệu

> **Cơ chế build theo theme chỉ tách CSS, JavaScript và giao diện. Nó không tự tách dữ liệu database.**

Database hiện có thể chứa dữ liệu của Khuyến nông, Nam Bình Dương và Ban Đề án. Vì vậy:

- Single-theme **không** đồng nghĩa với dữ liệu đã được cách ly.
- Bản triển khai riêng nên dùng **database riêng** cho từng dự án.
- Nếu dùng chung database, nội dung cần có khóa phạm vi như `project_key`, `site_id` hoặc `theme_key`, và mọi truy vấn phải lọc theo khóa đó.
- Không dùng tiền tố tiêu đề `NBD`/`BDA` làm cơ chế phân tách dữ liệu lâu dài.

### 8.9. Chọn chế độ triển khai

| Mục đích | Profile asset | Dữ liệu |
| --- | --- | --- |
| Phát triển khung | `all-themes` | Database master |
| Demo đổi theme | `all-themes` | Database master có dữ liệu các theme |
| Deploy Nam Bình Dương | `nam-binh-duong` | Database riêng NBD |
| Deploy Ban Đề án | `ban-de-an` | Database riêng BDA |
| Deploy Khuyến nông | `default` | Database riêng Khuyến nông |

### 8.10. Trạng thái kiểm tra (ngày 30/10/2026)

Đã đạt:
- Registry và `node scripts/theme-build.mjs <all|default|nam-binh-duong|ban-de-an> --check-only` (đối chiếu `@vite()` của view, kể cả view fallback). Danh sách entry của chế độ all-themes giống hệt cách glob trước đây (41 file).
- Kiểm tra cú pháp Node (`node --check`) và PHP (`php -l`) các file đã sửa/thêm.
- `AssetProfile` và `ScreenTheme::choices()` khi chạy với `CMS_ASSET_MODE`/`CMS_ASSET_THEME` khác nhau (kiểm tra bằng tinker, không ghi database).

**Chưa xác nhận** (không được coi là sẵn sàng production):
- Build thật của cả bốn profile (`all-themes`, `default`, `nam-binh-duong`, `ban-de-an`).
- Chạy dev thật (`dev:*`) và dừng bằng `Ctrl+C`.
- Giao diện trên trình duyệt ở từng profile, kể cả màn Cài đặt chung khi bị khóa theme.
- `theme-build.json` sau khi đưa vào `public/build` (máy local hiện có `public/hot` nên đường đọc metadata chưa được thử).

### 8.11. Chạy local theo dự án: nhóm lệnh `start:*`

Mỗi lệnh chạy cả Laravel (website `http://127.0.0.1:8011/`, quản trị `http://127.0.0.1:8011/cms`) và Vite trong một Terminal. Script `scripts/dev-all.ps1` tự đặt `CMS_PROJECT_KEY`, `CMS_ASSET_MODE`, `CMS_ASSET_THEME` **trong tiến trình** trước khi mở Laravel và Vite; không sửa `.env`. Nhóm `dev:*` ở 8.4 giữ nguyên.

| Lệnh | `-Project` (CMS_PROJECT_KEY) | `-Mode` (CMS_ASSET_MODE) | `-Theme` (CMS_ASSET_THEME) | Hiệu quả |
|---|---|---|---|---|
| `npm run start:nbd` | `nam-binh-duong` | `single` | `nam-binh-duong` | Dự án và giao diện Nam Bình Dương, cố định; khóa chọn giao diện |
| `npm run start:bda` | `ban-de-an` | `single` | `ban-de-an` | Dự án và giao diện Ban Đề án, cố định |
| `npm run start:kn` | `khuyen-nong` | `single` | `default` | Dự án Khuyến nông (giao diện `default`), cố định |
| `npm run start:all` | (xóa biến) | `all` | (xóa biến) | Mọi giao diện; dự án đi theo giao diện đang chọn trong quản trị |

Cách script xử lý:
- Dự án và theme phải khớp bảng ở `Modules/CmsBackend/config/projects.php`; lệnh sai (ví dụ `-Project ban-de-an -Theme default`) bị từ chối trước khi khởi động.
- **Config cache:** nếu có `bootstrap/cache/config.php`, script chạy `php artisan config:clear` trước khi mở Laravel, vì config đã cache làm `env()` đóng băng và giữ lại `CMS_PROJECT_KEY` của lần chạy trước. Chỉ xóa file cache, không đụng dữ liệu. Biến của lần chạy hiện tại luôn ghi đè giá trị cũ trong Terminal.
- `start:all` xóa `CMS_PROJECT_KEY` khỏi tiến trình. Nếu `.env` có sẵn giá trị cho biến này thì giá trị đó vẫn có hiệu lực; script cảnh báo trong trường hợp đó, hãy để trống trong `.env` khi dùng `start:all`.
- Cổng 8011 đang bị tiến trình khác dùng thì script dừng, không tắt tiến trình đó.
- `Ctrl+C` dừng Vite rồi tắt tiến trình PHP mà script đã mở (khối `finally`).
- Xem trước mà không khởi động gì: `npm run start:nbd -- -DryRun` (in biến sẽ đặt và tình trạng config cache).
- `dev:*` cũng xóa `CMS_PROJECT_KEY` của Terminal khi chạy, nên dự án đi theo giao diện của phiên đó.

Trạng thái kiểm tra: đã thử `-DryRun` cả bốn lệnh (kể cả khi Terminal có sẵn `CMS_PROJECT_KEY`/`CMS_ASSET_THEME` cũ: được ghi đè hoặc xóa đúng), từ chối lệnh sai cặp dự án/theme, và `ProjectContext`/`ScreenTheme`/`AssetProfile` đọc đúng biến từ tiến trình. **Chưa thử** khởi động thật bằng `start:*` và `Ctrl+C` (cổng 8011 đang bận trên máy lúc kiểm tra).

### 8.12. An toàn database dev khi kiểm thử

Database `cms_workspace` là dữ liệu phát triển thật, được phép dùng để chạy ứng dụng và kiểm tra thủ công bằng trình duyệt. **Tuyệt đối không chạy PHPUnit hoặc test có `RefreshDatabase` trên database này.** `RefreshDatabase` có thể gọi `migrate:fresh`, xóa toàn bộ bảng và dữ liệu dù môi trường mang tên `testing`.

Quy tắc bắt buộc:

- Không chạy `php artisan test`, `vendor/bin/phpunit`, `migrate:fresh`, `db:wipe` hoặc test có `RefreshDatabase` khi `DB_DATABASE=cms_workspace`.
- Muốn kiểm tra trực tiếp trên dữ liệu dev: dùng trình duyệt, request chỉ đọc hoặc thao tác có kiểm soát; không dùng test tự làm mới database.
- Muốn chạy PHPUnit có truy cập DB: phải chỉ định một database thử riêng, không chứa dữ liệu dev/production và có tên khác `cms_workspace`.
- `tests/TestCase.php` có chốt an toàn, dừng PHPUnit ngay trước khi Laravel khởi tạo test nếu phát hiện database là `cms_workspace`. Không gỡ hoặc né chốt này.
- Trước mọi migration hoặc phục hồi dữ liệu quan trọng, phải có bản dump xác minh được và ghi rõ database đích.

Sự cố ngày 02/11/2026: toàn bộ PHPUnit được chạy trong khi `phpunit.xml` không cấu hình database test riêng; các test Auth/Profile dùng `RefreshDatabase` đã làm rỗng `cms_workspace`. Dữ liệu được phục hồi từ bản Nam Bình Dương trên máy 228. Đây là nguyên nhân và quy tắc phòng ngừa chính thức.

### 8.13. Developer Settings: bật và truy cập

Trang Developer Settings (Permission Management và Theme Management) chỉ dùng **một** biến môi trường, `DEVELOPER_ACCESS_TOKEN_HASH`:

1. Chọn một mật khẩu Developer (không đặt vào mã nguồn, tài liệu hay lịch sử chat).
2. Băm bằng Laravel:
   ```bat
   php artisan tinker --execute="echo Hash::make('mat-khau-developer');"
   ```
3. Khai báo kết quả vào `.env`, **bọc trong dấu nháy đơn** vì hash chứa ký tự `$`:
   ```env
   DEVELOPER_ACCESS_TOKEN_HASH='$2y$12$...'
   ```
4. Sau đó chạy `php artisan config:clear` (nếu đang dùng config cache).

- Biến thiếu hoặc rỗng: mọi route Developer Settings trả 404. Có giá trị: chức năng bật. Không có công tắc bật/tắt nào khác.
- Developer Settings **tách khỏi đăng nhập CMS**: hai route mở khóa và Developer Settings không dùng middleware `auth` và không kiểm tra vai trò `Quản trị`. Chỉ cần `DEVELOPER_ACCESS_TOKEN_HASH` và mật khẩu Developer. Nhấn logo 5 lần (ở trang quản trị hoặc trang đăng nhập) hoặc mở thẳng `/cms/dashboard/developer-settings` đều đưa tới form nhập mật khẩu Developer, không chuyển tới trang đăng nhập CMS.
- Mật khẩu được kiểm tra bằng `Hash::check()`; sai thì báo chung và bị giới hạn 5 lần/phút theo địa chỉ IP. Khi đúng, Laravel đổi session ID rồi ghi `developer_unlocked` vào session. Cờ này chỉ mở các route Developer Settings (Permission Availability và Theme Management); không cấp quyền vào các chức năng quản trị CMS khác. Hết khi session hết hạn, khi session bị hủy (đăng xuất CMS) hoặc khi nhấn "Khóa"; không có thời hạn riêng.
- Audit log của Developer Settings ghi `actor = developer_session` cùng project, IP và thay đổi; không có `user_id`.
- Manifest quyền khả dụng (`storage/app/developer/permission-manifest.json`) được ký bằng `APP_KEY` sẵn có. Đổi `APP_KEY` thì manifest cũ không còn hợp lệ (hệ thống vào chế độ bảo vệ cho đến khi lưu lại hoặc khôi phục bản hợp lệ).
- Các biến `DEVELOPER_SETTINGS_ENABLED`, `DEVELOPER_MANIFEST_KEY`, `DEVELOPER_ALLOWED_IPS`, `DEVELOPER_SESSION_TTL` (và `DEVELOPER_UNLOCK_TTL`) không còn được đọc; không cần khai báo.

## Theme Nam Bình Dương — lịch sử bản đầu ngày 21/10/2026

Phần này ghi lại bản đầu; cấu trúc đang dùng theo bản duyệt nằm ở mục “Cập nhật theo bản thiết kế đã duyệt” bên dưới.

- Mã theme: `nam-binh-duong`, nhãn `Nam Bình Dương (SBUT)`. Chỉ đăng ký surface `public`; quản trị luôn dùng default.
- Nhận diện: Trường Đại học Công nghệ Nam Bình Dương; thông điệp Khai phóng – Ưu tú – Cống hiến. Xanh #003820/#004B2C/#005832/#00663D/#008A52; vàng #F3B23A/#FFD88C; cam #F45A00/#FF5F00; nền #FFF8EA; chữ #10211F/#5C706C.
- Font khai báo: Segoe UI, Be Vietnam Pro, Noto Sans, Arial, Tahoma, sans-serif. Dùng font cài trên máy, không tải font chữ từ dịch vụ bên ngoài.
- Bản đầu dùng nhận diện chữ/CSS tạm. Bản hiện tại đã thay bằng logo và ảnh campus lấy từ bản thiết kế SBUT được người dùng chỉ định; ảnh campus được chú thích là phối cảnh.
- Không đổi nội dung bài, danh mục, menu hoặc chức năng theo theme. Dữ liệu nông nghiệp còn trên website là nội dung giữ lại từ bản CMS ban đầu, cần biên tập riêng cho trường. Chưa khôi phục tuyển sinh hay nghiệp vụ trường học bị thiếu.

### Vị trí hiệu chỉnh từng cụm

Tất cả đường dẫn view dưới đây tính từ `Modules/CmsFrontend/resources/views/screens/nam-binh-duong/`:

| Cụm | Màn hình/component |
| --- | --- |
| Khung chung | layout.blade.php, components/layouts/commonMaster.blade.php |
| Header, menu, footer | components/layouts/sections/header/, navbar/, footer/ |
| Hero và truy cập nhanh | components/home/university-hero.blade.php, shortcuts.blade.php |
| Tin tức | components/home/news.blade.php, pages/frontend/tin-tuc/ |
| Thông báo, văn bản | components/home/information.blade.php, pages/frontend/van-ban/ |
| Lịch làm việc, liên kết cấu hình | components/home/schedules-trace.blade.php |
| Chuyên mục, sự kiện, gương tiêu biểu | components/home/activities.blade.php; pages/frontend/su-kien/, guong-tieu-bieu/ |
| Dịch vụ | components/home/services.blade.php, pages/frontend/dich-vu/ |
| Hình ảnh, video | components/home/media.blade.php; pages/frontend/hinh-anh/, video/ |
| Đối tác, hỗ trợ | components/home/partners-support.blade.php, pages/frontend/doi-tac/ |
| Hỏi đáp, gửi câu hỏi | pages/frontend/hoi-dap/ |
| Liên hệ, góp ý | pages/frontend/lien-he/ |

CSS riêng nằm ở `Modules/CmsFrontend/resources/assets/css/screens/nam-binh-duong/theme.css`. CSS hiện tại chuyển từ bản thiết kế đã duyệt và giới hạn toàn bộ selector trong .sbut-public; các trang con giữ hợp đồng dữ liệu và biểu mẫu hiện có. JS menu/tab/banner dùng lại xử lý hiện có; banner trang chủ lấy dữ liệu đã chuẩn bị phía server, không nạp script banner cũ ở trang con. Các view không ghi đè dùng fallback default. Controller/service, route, quyền, CSRF và kiểm tra dữ liệu giữ chung.

Đã build thành công và chạy nhóm 41 kiểm thử liên quan, đạt 2.024 assertions (theme SBUT, đổi theme, cách ly source, xóa bài và quy trình quản trị); kiểm thử HTTP dùng transaction rollback. Chưa chạy toàn bộ test suite và chưa nghiệm thu mọi thao tác CRUD.

Bản sao trước khi thêm theme và giá trị lựa chọn theme trước đó: `D:\DuAn\CMS-backups\theme-sbut-2026-10-21-01`.

Kiểm tra trực quan: trang chủ ở 1440px và 390px, menu di động đóng/mở, trang liên hệ và bộ lọc tin tức trên điện thoại. Đã sửa bộ lọc để không tràn ngang. Bản kiểm tra dùng trình duyệt thật; chưa kiểm tra mọi trang ở mọi kích thước.

## Cập nhật theo bản thiết kế đã duyệt — 21/10/2026

Tham chiếu: https://sbut-campus-cms.chicfir5.chatgpt.site (phiên bản 4, commit a98bb8f182956ee21200026b068690eab638ebdf), được xác định từ cuộc trò chuyện người dùng gửi. Chỉ đọc bản tham chiếu; không sửa, triển khai hoặc thay quyền truy cập website đó.

### Giao diện

- Header logo SBUT, menu Trang chủ / Giới thiệu / Đào tạo / Nghiên cứu / Tin tức / Tuyển sinh; menu cuộn đến section trong cùng landing page, từ trang con quay về đúng section.
- Banner 3 phối cảnh campus và nút chuyển ảnh; không tự phát video hoặc tự chuyển banner.
- Đủ cụm: banner; chương trình đào tạo; số liệu nổi bật; nghiên cứu; tin tức–sự kiện; thông báo tuyển sinh; liên kết sinh viên/giảng viên; video; đối tác. Có thêm giới thiệu trường và lối vào các chức năng CMS khác.
- Các cụm đào tạo/nghiên cứu/tin tức/video dùng danh sách từng hàng. Hai nhóm sinh viên và giảng viên hiển thị cùng lúc, mỗi nhóm 6 mục.
- Tin/sự kiện lọc tại trang; tìm kiếm gửi đến tìm kiếm CMS; nút phát video mở trang phát video thật. Các nghiệp vụ cổng sinh viên/lịch học/hồ sơ khoa học chưa khôi phục không có thao tác mẫu giả.
- Không nhập số liệu, chương trình, thành tựu hoặc tuyển sinh minh họa từ bản mẫu vào DB. Các mục chưa có dữ liệu hiện đang cập nhật; số liệu trống dùng dấu —.

### Nguồn nội dung và cách cập nhật

Dùng chức năng bài viết/chuyên mục hiện có. Tạo bài công khai, đã đăng và còn hạn trong các chuyên mục tương ứng; không cần controller hay database riêng cho theme. Slug ánh xạ nằm tại `Modules/CmsBackend/config/screens.php` → `themes.nam-binh-duong.home_sections`:

| Cụm | Slug chuyên mục mặc định | Component đang sử dụng |
| --- | --- | --- |
| Giới thiệu | gioi-thieu | introduction.blade.php |
| Chương trình đào tạo tiêu biểu | chuong-trinh-dao-tao | programs.blade.php |
| Thành tựu nghiên cứu | thanh-tuu-nghien-cuu | research.blade.php |
| Thông báo tuyển sinh | thong-bao-tuyen-sinh | admissions.blade.php |

Các component trên nằm trong `Modules/CmsFrontend/resources/views/screens/nam-binh-duong/components/home/`. Mỗi cụm đọc tối đa 4 bài, eager-load media và chuyên mục; loại bài nháp, hẹn giờ chưa đến hạn, hết hạn hoặc có chuyên mục nội bộ. Đây là khối hiển thị nội dung; chưa phải khôi phục module quản lý ngành học, nghiên cứu hoặc hồ sơ tuyển sinh chuyên biệt từ Sóc Trăng.

Số liệu đã xác nhận đặt trong `themes.nam-binh-duong.statistics`; chưa có số xác nhận thì giữ null. Đích đến của tiện ích ngoài CMS đặt trong `audience_links.student` và `audience_links.faculty` theo chỉ số 0–5; chưa có hệ thống/URL thì giữ trạng thái đang cập nhật. Không đặt mật khẩu hoặc token trong config. Khi chỉnh config ảnh hưởng fragment cache, tăng `version` của theme.

Tin tức/sự kiện, video và đối tác lấy dữ liệu CMS hiện hữu. Không đổi tên nội dung nông nghiệp cũ thành nội dung trường. Các chức năng văn bản, hình ảnh, dịch vụ, hỏi đáp, liên hệ và danh mục menu quản trị vẫn có đường dẫn truy cập.

CSS: `Modules/CmsFrontend/resources/assets/css/screens/nam-binh-duong/theme.css`. JS: `Modules/CmsFrontend/resources/assets/js/screens/nam-binh-duong/theme.js`. Logo/ảnh tham chiếu: `public/assets/img/themes/nam-binh-duong/`. Các trang con: `screens/nam-binh-duong/pages/frontend/`. Giữ theme mặc định và quản trị hiện tại.

Kiểm tra: build đạt; 43 kiểm thử / 2.053 assertions đạt. Đã kiểm tra desktop 1440px và điện thoại 390px: banner, menu cuộn/vị trí dừng, bộ lọc sự kiện, 6+6 tiện ích, không tràn ngang và biểu mẫu liên hệ. Chưa chạy toàn bộ test suite hoặc nghiệm thu mọi CRUD. Các bản ghi thử được rollback.

Sao lưu trước đợt này: `D:\DuAn\CMS-backups\theme-sbut-reference-2026-10-21-01`. Bản tham chiếu đọc về nằm ngoài source tại `D:\DuAn\CMS-backups\sbut-reference-2026-10-21`.

## Theme Ban Đề án — 26/10/2026

- Mã theme: `ban-de-an`, nhãn `Ban Đề án`; chỉ đăng ký cho trang công khai và không tạo theme quản trị.
- Bố cục tham chiếu: `https://ban-de-an-nam-binh-duong-portal.chicfir5.chatgpt.site/`. Trang chủ gồm hero, truy cập nhanh, tin tức–thông báo, đề án–dự án, giới thiệu–đơn vị đồng hành, văn bản–tài liệu và liên hệ.
- Nội dung lấy từ dữ liệu CMS hiện có. Các cụm chưa có dữ liệu hiển thị trạng thái đang cập nhật, không chèn bài hoặc dự án mẫu vào database.
- `themes.ban-de-an.home_sections` ánh xạ `gioi-thieu` và `de-an-du-an`. Menu tiếp tục lấy từ cấu hình menu và vai trò hiện hữu.
- View nằm tại `Modules/CmsFrontend/resources/views/screens/ban-de-an/`; CSS/JS tại `Modules/CmsFrontend/resources/assets/{css,js}/screens/ban-de-an/`.
- Các trang chức năng được giữ đủ theo cấu trúc screen của theme. Controller, service, route, quyền, CSRF và database dùng chung; thêm theme không tạo module nghiệp vụ mới.
- Build CMS đã đạt và manifest có đủ CSS/JS `ban-de-an`. Chưa chạy PHPUnit trong lượt này vì máy không tìm thấy PHP CLI tại các vị trí Laragon cũ.
