# Homepage Layout Manager — hướng dẫn thêm cụm, biến thể, hiệu ứng

Kiến trúc đầy đủ: `MASTER-PLAN.md`. Mọi khai báo nằm trong `Modules/CmsFrontend/app/Homepage/`.

## Trang chủ
- Trang chủ của mọi theme luôn dựng theo bố cục (database; chưa cài thì dùng bố cục mặc định khai báo trong code). Không còn chế độ Legacy.
- Developer sắp xếp trong Developer Settings → "Bố cục trang chủ": Xem trước → Áp dụng. Lùi lại bố cục: nút "Khôi phục mặc định" hoặc `php artisan homepage:layout-install <theme> --reset`.
- Settings theo theme lưu ở `homepage_block_settings`; option cũ chỉ để khởi tạo một lần khi cài bố cục (`homepage:layout-install`).

## Cụm mới chỉ khác nguồn nội dung
Khai báo thêm instance trong `blocks()` của Theme Layout (`Themes/*HomepageLayout.php`) trỏ vào `definition` có sẵn (ví dụ `section`), rồi chạy `php artisan homepage:layout-install <theme>` và chọn nguồn ở Cài đặt trang chủ. Không cần Blade mới nếu dùng lại renderer.

## Loại cụm mới
1. Tạo `Definitions/XxxBlock.php` (kế thừa `AbstractBlockDefinition`): `key`, `icon`, schema cấu hình, `resolve()` (dữ liệu theo settings của theme), `prefetch()` nếu nhiều instance cùng loại.
2. Thêm vào `HomepageBlockRegistry::DEFINITIONS`.
3. Thêm `homepage.blocks.<key>.name|description` và nhãn field vào `lang/vi|en/homepage.php`.
4. Mỗi theme hỗ trợ: tạo partial riêng dưới `components/home/` của chính theme, khai báo trong `blocks()` (zone, variant, effect) và `defaultLayout()`.
5. Nếu partial của cụm đọc dữ liệu nền của trang chủ (bài mới, sự kiện, văn bản, đối tác, video...), khai báo loại cụm trong `HomepageDataNeeds::BY_DEFINITION` để trang chủ chỉ truy vấn nhóm đó khi cụm đang bật. Chưa khai báo thì trang chủ nạp đủ mọi nhóm (đúng nhưng chậm hơn).
6. Build và deploy một lần. Sau đó bật/tắt, sắp xếp, đổi variant trong Developer Settings.

## Variant, hiệu ứng
- Variant mới: thêm khóa vào `variants` của cụm kèm `renderer` (partial riêng).
- Hiệu ứng thuộc khung dùng chung (mọi theme dùng được, mọi build đều đóng gói): Theme Layout chỉ khai báo KEY preset trong `EffectCatalog` (không lưu JS/CSS/selector trong database). Trang chủ chỉ nạp entry của preset mà cụm đang bật khai báo.
- Preset mới: file nguồn trong `effects/<tên>/`, entry tự khởi chạy `effects/auto/<tên>.js`, thêm vào `EffectCatalog::PRESETS/ENTRIES`, `sharedEffectEntries` trong `scripts/theme-profiles.mjs`, `@vite` có điều kiện trong `home.blade.php` của các theme, và `effects/EFFECTS.md`. Kiểm: `node scripts/theme-build.mjs all --check-only`.
- Chọn hiệu ứng cho từng cụm (tab Bố cục trang chủ): cột `effect_key` (migration `2026_11_02_200001_add_effect_key_to_homepage_theme_blocks`, chạy bằng `--path`). Trống = hiệu ứng mặc định của theme (HTML partial nguyên vẹn: Nam Bình Dương/Ban Đề án giữ hiệu ứng hiện tại, Khuyến nông không có); `none` gỡ reveal/stagger; `reveal` (chữ hiện theo dòng) và `stagger` (trồi lên lần lượt) gắn hook vào HTML đã render của cụm (`BlockEffectApplier`, không bọc thêm phần tử). Hiệu ứng cấu trúc (slider banner, vòng ảnh, đếm số) không đổi bằng lựa chọn. Thêm lựa chọn mới = thêm vào `EffectCatalog::CHOICES` và `BlockEffectApplier`.
- Hành vi riêng của một theme (hero-slider, scroll-spy, rail, hiệu ứng riêng) ở lại `js/screens/<theme>/theme.js`.

## Test an toàn
- `phpunit.xml` trỏ DB tới `cms_phpunit_blocked` (không tồn tại) để PHPUnit không bao giờ chạm database dev.
- `vendor/bin/phpunit tests/Unit/Homepage` không cần database.
- Test cần bảng thật: `HLM_TEST_DATABASE=<db có chữ "test"> DB_DATABASE=<db đó> CMS_DB_DATABASE=<db đó> vendor/bin/phpunit tests/Feature/Homepage` (database thử đã có schema CMS, chưa có 3 bảng của chức năng).
- KHÔNG chạy toàn bộ `vendor/bin/phpunit` trên database có dữ liệu thật: nhiều test cũ dùng `RefreshDatabase`.
