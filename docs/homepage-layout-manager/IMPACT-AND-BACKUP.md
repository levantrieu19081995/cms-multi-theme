# HOMEPAGE LAYOUT MANAGER — PHẠM VI ẢNH HƯỞNG VÀ BACKUP

> Tài liệu bắt buộc đọc cùng `MASTER-PLAN.md` trước khi triển khai.
>
> Mục tiêu: biết chính xác phần nào có thể bị ảnh hưởng, backup đủ để hoàn tác nhưng không sao chép toàn bộ source hoặc dữ liệu media không liên quan.

## 1. Phạm vi code chắc chắn bị ảnh hưởng

### 1.1. Lõi trang chủ Frontend

Các file hiện đang tổng hợp và trình bày dữ liệu trang chủ:

```text
Modules/CmsFrontend/app/Http/Controllers/HomeController.php
Modules/CmsFrontend/app/Services/HomePresentation.php
Modules/CmsFrontend/app/Services/HomeTheme.php
Modules/CmsFrontend/app/Services/HomeBlocks.php
```

Hướng thay đổi:

- Thêm lớp Homepage Registry, Layout Service, Settings Service và Data Resolver.
- Giữ controller hiện tại làm entry point; không viết lại toàn bộ truy vấn ngay một lần.
- Trong giai đoạn chuyển đổi, theme ở chế độ `legacy` tiếp tục đi theo luồng hiện tại.

### 1.2. View trang chủ của ba theme

```text
Modules/CmsFrontend/resources/views/screens/default/home.blade.php
Modules/CmsFrontend/resources/views/screens/default/components/home/

Modules/CmsFrontend/resources/views/screens/nam-binh-duong/home.blade.php
Modules/CmsFrontend/resources/views/screens/nam-binh-duong/components/home/

Modules/CmsFrontend/resources/views/screens/ban-de-an/home.blade.php
Modules/CmsFrontend/resources/views/screens/ban-de-an/components/home/
```

Hướng thay đổi:

- Không viết lại giao diện cũ.
- Renderer mới ưu tiên include hoặc bọc lại partial hiện có.
- Giữ nguyên HTML class, `data-*`, ID, responsive và hook effect.
- Mỗi theme chỉ chuyển sang renderer mới sau khi preview đạt.

### 1.3. CSS, JavaScript và effect trang chủ

```text
Modules/CmsFrontend/resources/assets/css/screens/default/
Modules/CmsFrontend/resources/assets/css/screens/nam-binh-duong/
Modules/CmsFrontend/resources/assets/css/screens/ban-de-an/

Modules/CmsFrontend/resources/assets/js/screens/default/
Modules/CmsFrontend/resources/assets/js/screens/nam-binh-duong/
Modules/CmsFrontend/resources/assets/js/screens/ban-de-an/

Modules/CmsFrontend/resources/assets/effects/
Modules/CmsFrontend/resources/assets/shared/
scripts/theme-profiles.mjs
```

Chỉ backup các file thực sự được sửa trong các thư mục này. Không sửa CSS/JS chỉ để đổi cấu trúc PHP nếu renderer vẫn dùng được hook hiện tại.

### 1.4. Developer Settings

```text
Modules/CmsBackend/app/Http/Controllers/DeveloperSettingsController.php
Modules/CmsBackend/app/Services/DeveloperSettings.php
Modules/CmsBackend/resources/views/screens/default/pages/developer-settings/
Modules/CmsBackend/routes/web.php
```

Phần mới dự kiến:

- Tab Bố cục trang chủ.
- API/route lưu working layout, tạo preview snapshot và áp dụng live layout.
- Trang Review/Preview.
- Quyền truy cập vẫn dùng Developer session hiện có; không nhập chung với tài khoản CMS.

### 1.5. Cài đặt trang chủ

```text
Modules/CmsBackend/app/Http/Controllers/SettingController.php
Modules/CmsBackend/app/Support/HomeSettings.php
Modules/CmsBackend/resources/views/screens/default/pages/setting/general.blade.php
Modules/CmsBackend/resources/views/screens/default/pages/setting/partials/
Modules/CmsFrontend/routes/web.php
```

Hướng thay đổi:

- Form động được sinh từ block đang bật và `settingsSchema()`.
- Giai đoạn đầu phải giữ adapter đọc/ghi option cũ.
- Không xóa option cũ cho tới khi toàn bộ theme đã chuyển đổi và nghiệm thu.

### 1.6. Config, service provider, ngôn ngữ và build

Có thể bị ảnh hưởng khi đăng ký service hoặc thêm nhãn giao diện:

```text
Modules/CmsBackend/config/screens.php
Modules/CmsBackend/app/Providers/CmsBackendServiceProvider.php
Modules/CmsFrontend/app/Providers/
lang/vi/admin.php
lang/en/admin.php
lang/vi/frontend.php
lang/en/frontend.php
scripts/theme-profiles.mjs
```

Chỉ sửa file nào thực sự cần. Không đưa renderer, Blade path, JavaScript hoặc CSS selector tùy ý vào database.

### 1.7. Database

Chỉ thêm migration cho các bảng mới của Homepage Layout Manager, dự kiến:

```text
homepage_block_instances
homepage_theme_blocks
homepage_block_settings
```

Nếu preview snapshot dùng cache/session thì không cần bảng preview riêng trong giai đoạn đầu.

Các bảng cũ có liên quan cần bảo vệ:

```text
options
option_translations
```

Không sửa dữ liệu bài viết, chuyên mục, văn bản, sự kiện, ảnh, video hoặc khảo sát trong phase xây dựng layout.

## 2. Phạm vi không được ảnh hưởng

- Controller, route và quy trình CRUD của bài viết, văn bản, sự kiện, ảnh, video, khảo sát, liên hệ.
- Cơ chế đăng nhập, phân quyền CMS và Developer unlock.
- Menu công khai và menu quản trị, trừ khi chỉ bổ sung link tới chức năng mới.
- Dữ liệu nghiệp vụ hiện có.
- Upload/media trong `public` hoặc `storage`.
- Login theo theme và Auth transition.
- Feature Registry hiện tại.
- Nginx, domain, container và cấu hình deploy của các dự án trên 228.

## 3. Vị trí backup chuẩn

Mỗi lần bắt đầu một phase, tạo một thư mục mới, không ghi đè backup cũ:

```text
storage/app/backup-homepage-layout-manager-YYYYMMDD-HHMMSS/
├── README.txt
├── files.txt
├── source/
└── database/
```

Ý nghĩa:

- `README.txt`: phase, thời điểm, mục tiêu và trạng thái source lúc backup.
- `files.txt`: danh sách chính xác file/thư mục được sao chép.
- `source/`: giữ nguyên đường dẫn tương đối từ root dự án.
- `database/`: chứa file dump DB trước migration hoặc trước khi chuyển live.

Không đặt backup vào `Modules/`, `resources/`, `public/` hoặc thư mục build vì có thể bị Agent quét nhầm và đóng gói vào bản build.

## 4. Backup nền trước Phase 1

Backup một lần các phần hiện trạng quan trọng:

```text
Modules/CmsFrontend/app/Http/Controllers/HomeController.php
Modules/CmsFrontend/app/Services/HomePresentation.php
Modules/CmsFrontend/app/Services/HomeTheme.php
Modules/CmsFrontend/app/Services/HomeBlocks.php

Modules/CmsFrontend/resources/views/screens/default/home.blade.php
Modules/CmsFrontend/resources/views/screens/default/components/home/
Modules/CmsFrontend/resources/views/screens/nam-binh-duong/home.blade.php
Modules/CmsFrontend/resources/views/screens/nam-binh-duong/components/home/
Modules/CmsFrontend/resources/views/screens/ban-de-an/home.blade.php
Modules/CmsFrontend/resources/views/screens/ban-de-an/components/home/

Modules/CmsFrontend/resources/assets/css/screens/
Modules/CmsFrontend/resources/assets/js/screens/
Modules/CmsFrontend/resources/assets/effects/

Modules/CmsBackend/app/Http/Controllers/DeveloperSettingsController.php
Modules/CmsBackend/app/Http/Controllers/SettingController.php
Modules/CmsBackend/app/Services/DeveloperSettings.php
Modules/CmsBackend/app/Support/HomeSettings.php
Modules/CmsBackend/resources/views/screens/default/pages/developer-settings/
Modules/CmsBackend/resources/views/screens/default/pages/setting/

Modules/CmsBackend/routes/web.php
Modules/CmsFrontend/routes/web.php
Modules/CmsFrontend/routes/public.php
Modules/CmsBackend/config/screens.php
scripts/theme-profiles.mjs
```

Không cần backup:

```text
vendor/
node_modules/
public/assets/uploads/
storage/app/public/
build-exports/
```

Các thư mục trên hoặc có thể cài/build lại, hoặc là dữ liệu media không bị chức năng này chỉnh sửa.

## 5. Backup theo từng phase

Trước mỗi phase:

1. Agent phải liệt kê trước các file dự kiến sửa.
2. Sao chép đúng các file đó vào một thư mục backup timestamp mới, giữ nguyên đường dẫn tương đối.
3. Ghi danh sách vào `files.txt`.
4. File mới chưa tồn tại thì ghi vào `files.txt` với trạng thái `NEW`; không cần tạo file backup rỗng.
5. Chỉ sau khi backup xong mới được sửa code.

Không copy lại toàn bộ project ở mỗi phase. Backup nền + backup file thay đổi theo phase là đủ và dễ hoàn tác.

## 6. Backup database

### Trước migration đầu tiên

- Dump toàn bộ database dev hiện tại nếu dung lượng cho phép.
- Nếu buộc phải dump tối thiểu thì phải có schema hiện tại và dữ liệu của `options`, `option_translations`.
- Ghi kết quả `migrate:status` vào `database/migrate-status-before.txt`.

### Trước khi chuyển một theme từ legacy sang managed

Dump tối thiểu:

```text
options
option_translations
homepage_block_instances
homepage_theme_blocks
homepage_block_settings
```

Không chạy `php artisan migrate` chung nếu database còn migration cũ Pending. Chỉ chạy đúng migration của chức năng này bằng `--path` sau khi đã xem `migrate:status`.

## 7. Backup build và deploy

Trong giai đoạn viết code không cần sao chép `public/build` hoặc `build-exports`.

Chỉ trước lúc deploy:

- Giữ nguyên image/container hoặc release đang chạy trên 228.
- Tạo release mới, không ghi đè trực tiếp release cũ.
- Backup `.env` riêng trên server; không đưa App Secret hoặc mật khẩu vào source backup.
- Backup DB của đúng dự án đang deploy.
- Không đụng container, volume, Nginx hoặc database của dự án khác.

## 8. Cách hoàn tác

### Hoàn tác code trong khi đang làm

- Dựa vào `files.txt` của phase.
- Chỉ chép trả lại các file đã sửa.
- Xóa đúng những file được đánh dấu `NEW` của phase đó nếu cần hoàn tác.
- Không chép đè toàn bộ project vì có thể làm mất thay đổi hợp lệ của phase khác.

### Hoàn tác theme đã chuyển live

1. Đổi theme về chế độ `legacy`.
2. Xóa cache layout.
3. Trang chủ phải quay lại renderer cũ ngay, chưa cần restore DB.
4. Chỉ restore DB nếu migration hoặc dữ liệu cấu hình đã hỏng.

### Hoàn tác deploy

- Chuyển Nginx/container về release hoặc image trước đó.
- Không build lại tại server để hoàn tác.
- Chỉ restore database của đúng dự án nếu có thay đổi schema/data không tương thích.

## 9. Quy tắc bắt buộc cho Agent

- Không sửa code trước khi tạo backup phase.
- Không backup bằng cách sao chép toàn bộ `cms-multi-theme`.
- Không lưu token, secret, mật khẩu hoặc nội dung `.env` vào backup source.
- Không tự xóa backup sau khi hoàn thành.
- Không dùng backup của phase sau để hoàn tác phase trước.
- Báo cáo cuối phase phải ghi đường dẫn backup, file đã sửa, file mới và migration đã tạo/chạy.

