# Khuyến nông Cần Thơ — giai đoạn dùng chung dữ liệu

Cặp module gồm `KhuyenNongCanThoBE` và `KhuyenNongCanThoFrontPage`, nhân bản từ cặp Sóc Trăng ngày 07/10/2026. Bản này kế thừa giao diện và chức năng hiện có, kể cả tuyển sinh, khảo sát và tự xem PDF. Chưa đổi nội dung, logo hoặc dữ liệu thành dữ liệu Khuyến nông.

## Đường dẫn local

- Frontend: http://cms_khung.test/khuyen-nong
- Quản trị: http://cms_khung.test/khuyen-nong/dashboard
- Bài viết: http://cms_khung.test/khuyen-nong/dashboard/cdn/bai-viet
- Mọi route của cặp mới có tiền tố tên `knct.`. Các chức năng chung như đăng nhập, người dùng, vai trò và quản lý tập tin tiếp tục dùng khung chung.

Cần bật web server local. Tài khoản/quyền sử dụng dữ liệu hiện tại. Không có tài khoản hoặc quyền mới được seed trong lần nhân bản này.

## Chạy riêng từng dự án trên local

Bật Laragon, vào terminal tại thư mục `CMS_Khung`:

- Sóc Trăng: `npm run dev:soctrang`.
- Khuyến nông: `npm run dev:khuyennong`.

Mỗi lệnh bật đúng cặp BE/FE và tắt cặp còn lại trong `modules_statuses.json`, giữ nguyên trạng thái các module khác. Sau đó làm mới cache cấu hình, route, module, view và menu rồi chạy Vite. Không chạy migration/seeder và không sửa dữ liệu. Khi đổi dự án, dừng Vite bằng `Ctrl+C` trước rồi chạy lệnh còn lại. Nếu phát hiện Vite cũ còn chạy, lệnh sẽ dừng trước khi đổi trạng thái.

Với Khuyến nông, mở `/khuyen-nong` và `/khuyen-nong/dashboard`; khi chỉ bật cặp Khuyến nông, `/` và `/dashboard` cũng chuyển đến các địa chỉ tương ứng để đăng nhập/đăng xuất từ khung chung hoạt động. Sóc Trăng giữ `/` và `/dashboard` như cũ.

Nếu chỉ cần chọn dự án mà không chạy Vite, dùng `npm run project:soctrang` hoặc `npm run project:khuyennong`. Trạng thái được giữ sau khi dừng Vite. `npm run dev` vẫn chạy theo trạng thái module hiện có; `npm run build` build các module đang bật. Các lệnh chọn dự án này phục vụ local, không cần chạy trên server Sóc Trăng.

## Build riêng để đưa lên server

| Lệnh | Thành phần | Kết quả |
| --- | --- | --- |
| `npm run build:khuyennong` | Khung, theme quản trị, BE/FE Khuyến nông | `build-exports/khuyennong/build/` |
| `npm run build:soctrang` | Khung, theme quản trị, BE/FE Sóc Trăng | `build-exports/soctrang/build/` |
| `npm run build:khung` | Chỉ khung và theme quản trị | `build-exports/khung/build/` |

Lệnh build riêng không đổi trạng thái module hoặc `public/build` local, nên không cần đổi dự án đang làm để build dự án khác. Khung đã có sẵn trong bản build Khuyến nông/Sóc Trăng, không cần build và ghép khung thêm lần nữa. Bản chỉ khung không đủ asset để chạy hai cặp module này.

Upload toàn bộ nội dung thư mục `build` của đúng dự án vào `public/build` trên server của dự án đó, gồm `manifest.json` và `assets/`. URL trong asset vẫn là `/build/...`, không phải `/build-exports/...`. Đây là bản build để xuất/triển khai; local vẫn dùng Vite hoặc `npm run build` thông thường. Cần đồng bộ source PHP/Blade và bật đúng module trên server tương ứng.

## Phạm vi tách

- Namespace, view, asset, breadcrumb và middleware quyền riêng cho cặp mới.
- Layout/menu quản trị được sao chép vào module mới để không thay menu các module cũ. Menu JSON mới dùng tên `project-verticalMenu.json` và `project-horizontalMenu.json`, tránh cơ chế tự gộp menu chung.
- Những controller của khung cần tạo URL riêng được sao chép vào `Http/Controllers/Shared` của module này. Các file `app/` gốc không bị chỉnh.
- Ảnh tĩnh được sao chép sang `public/assets/img/khuyen-nong-can-tho`.
- Có thể chạy đồng thời hoặc chọn riêng một cặp bằng các lệnh local ở trên. Trạng thái các module khác được giữ nguyên.

## Cấu hình dữ liệu

Các giá trị mặc định trong cấu hình module:

```dotenv
KNCT_ROUTE_PREFIX=khuyen-nong
KNCT_SHARE_SOURCE_MEDIA=true
KNCT_REGISTER_MIGRATIONS=false
```

Không cần thêm các dòng này vào `.env` để chạy chế độ mặc định. `.env` hiện tại và kết nối database không được sửa.

`KNCT_SHARE_SOURCE_MEDIA=true` giữ định danh model media của Sóc Trăng cho văn bản, marquee, đối tác và gương tiêu biểu. Nhờ đó ảnh/file đang lưu theo namespace cũ vẫn đọc được. Không chuyển giá trị này sang false khi còn dùng các bản ghi media cũ mà chưa xử lý lại `model_type`.

Migration của bản nhân bản chưa đăng ký để tránh tác động schema hiện tại. Các file migration vẫn được sao chép đầy đủ cho bước khởi tạo database riêng sau này.

**Hiện hai bản dùng cùng các bảng và file upload. Thao tác lưu/xóa bài, ảnh, cài đặt hoặc đổi quyền ở bản mới sẽ tác động dữ liệu chung.** Namespace và URL riêng không cách ly dữ liệu. Kiểm thử ghi/xóa thật cần database và kho file thử nghiệm riêng.

## Triển khai source

Thêm hai thư mục module mới và ảnh tĩnh mới; giữ các module cũ. Khi cần bật cặp mới, thêm hai mục tương ứng vào file trạng thái module mà không đổi các mục cũ.

```bash
composer dump-autoload --no-scripts
npm run build
php artisan view:clear
```

Nếu server có cache route/module, cần làm mới các cache đó để nhận route/module mới. Không chạy migration hoặc seeder trên database Sóc Trăng cho bước nhân bản này. Chưa triển khai bản này lên server.

Bước tách database, cache, storage, tài khoản và cấu hình thương hiệu được thực hiện riêng sau khi chốt kiểm thử. Cần kiểm tra lại route/auth và định danh media khi chuyển chế độ triển khai.
