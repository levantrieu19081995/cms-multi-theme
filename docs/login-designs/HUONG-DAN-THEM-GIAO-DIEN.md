# Giao diện đăng nhập: thêm bộ mới

Trang đăng nhập (và đăng ký) là một **bộ giao diện riêng**, không phụ thuộc theme website. Phần điều khiển (`LoginController`,
`AuthenticatedSessionController`, `RegisteredUserController`, `AuthPageContext`) dùng chung cho mọi dự án và **không đổi** khi thêm bộ mới.
Mỗi dự án chọn một bộ trong **Developer Settings → tab Đăng nhập**; dự án chưa chọn dùng bộ cùng tên với theme của nó (giống trước đây).

## Một bộ = một thư mục

```
Modules/CmsFrontend/resources/views/auth/designs/<ten-bo>/
    login.blade.php   bố cục
    auth.css          kiểu (là một entry của Vite)
    design.php        tên hiển thị, mô tả, hiệu ứng chuyển trang
```

- `<ten-bo>`: chữ thường, số, dấu gạch ngang, bắt đầu bằng chữ (ví dụ `card-center`). Thư mục bắt đầu bằng `_` (như `_template`) bị bỏ qua.
- Thiếu một trong ba tệp thì bộ không được liệt kê (không báo lỗi trên trang đăng nhập).

## Thêm một bộ

1. Chép `auth/designs/_template/` thành `auth/designs/<ten-bo>/`.
2. Sửa `design.php` (nhãn, mô tả, `transition`), sửa `auth.css` (màu `--auth-*`, bố cục), sửa `login.blade.php` nếu cần khác bố cục.
3. Trong `login.blade.php` đổi dòng `@vite('Modules/CmsFrontend/resources/views/auth/designs/<ten-bo>/auth.css')` cho đúng thư mục.
4. Build và deploy như thường lệ (`npm run build:theme -- <theme>` hoặc `build:all-themes`). **Không cần sửa** `screens.php`, `theme-profiles.mjs`, route hay controller:
   `scripts/theme-profiles.mjs` tự thêm `auth/designs/*/auth.css` vào **mọi** bản build (cả bản một theme), nên dự án nào cũng chọn được.
5. Vào Developer Settings → Đăng nhập: bộ mới hiện trong danh sách (có nút **Xem thử**); chọn cho dự án rồi **Lưu**.

Bộ đã có thư mục nhưng chưa có trong bản build (chưa build/deploy) hiện nhãn **Chưa có trong bản build** và **không chọn được**; nếu một bộ đang được chọn mà
bị mất khỏi bản build, trang đăng nhập tự dùng bộ của theme (rồi `default`), không bao giờ lỗi 500.

## Hợp đồng dữ liệu

`login.blade.php` `@extends('cmsfrontend::auth.shared.auth-shell')` và bắt đầu bằng
`@php extract(\Modules\CmsFrontend\Support\AuthPageContext::make($authMode ?? null, $errors)); @endphp`. Các biến có sẵn:

| Biến | Ý nghĩa |
|---|---|
| `$brandName`, `$brandUnit`, `$brandLogo`, `$brandFavicon` | thương hiệu của dự án (theo theme, không theo bộ) |
| `$authMode`, `$isRegister`, `$canRegister` | đang ở chế độ đăng nhập hay đăng ký |
| `$loginError` | thông báo lỗi hiện tại |
| `$homeUrl`, `$pageTitles`, `$authTransition` | liên kết trang chủ, tiêu đề, hiệu ứng chuyển trang |

Section: `auth-assets` (`@vite` CSS của bộ), `auth-frame-attrs` (thuộc tính thêm cho khung), `auth-frame` (nội dung). Ghép các mảnh dùng chung trong
`auth/shared/`: `login-form`, `register-form`, `visual-panels`, `curtains`. Bộ **chỉ trình bày**: cần dữ liệu mới thì thêm vào `AuthPageContext`
(dùng chung), không tạo logic trong bộ. Ảnh riêng của bộ đặt dưới `public/assets/img/auth/` rồi gọi bằng `asset(...)`.

## Kiểm tra trước khi deploy

- `node scripts/theme-build.mjs all --check-only` (kiểm tra mọi `@vite` của bộ đều có trong bản build).
- `DB_CONNECTION=sqlite DB_DATABASE=:memory: CMS_DB_DATABASE=:memory: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/Login`.
- Developer Settings → Đăng nhập → **Xem thử** (khung xem thử không gửi được biểu mẫu), rồi thử đăng nhập, đăng ký và đăng xuất thật trên trình duyệt.

## Cách chọn bộ (để biết khi gỡ lỗi)

`Modules/CmsBackend/app/Services/LoginDesigns.php`: xem thử (Developer) → lựa chọn lưu cho dự án (option `cms_login_designs`, JSON `{"<dự án>": "<bộ>"}`)
→ bộ cùng tên với theme → `default` → bộ dùng được đầu tiên. Bộ không đủ tệp hoặc không có CSS trong build bị bỏ qua ở mọi bước.
