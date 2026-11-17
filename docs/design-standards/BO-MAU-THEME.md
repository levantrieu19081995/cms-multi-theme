# Bộ màu của các theme công khai

Bốn bộ màu, mỗi bộ đặt tên theo theme dùng nó. Ba bộ đầu lấy trực tiếp từ CSS đang chạy, kiểm tra ngày 12/11/2026. Bộ thứ tư lấy từ bản thiết kế mới, chưa có trong CSS. Khi đổi màu trong CSS thì cập nhật tệp này. Quy tắc trình bày chung nằm ở `TIEU-CHUAN-THIET-KE.md`.

| Bộ màu | Theme | Màu chủ đạo | Trạng thái | Nơi khai báo |
|---|---|---|---|---|
| **default** | `default` (Khuyến nông) | xanh lá `#006B43` + đỏ `#E81920` | đang chạy | `Modules/CmsFrontend/resources/assets/css/screens/default/shell.css` (dòng 7-10), `home.css` (dòng 446-449); phạm vi `.cms-public` |
| **nam-binh-duong** | `nam-binh-duong` | navy `#102A43` + vàng đồng `#C9943E` | đang chạy | `.../screens/nam-binh-duong/theme.css` (dòng 3); phạm vi `.sbut-public` |
| **ban-de-an** | `ban-de-an` | navy `#102A43` + vàng đồng `#C9943E` (giống nam-binh-duong) | đang chạy | `.../screens/ban-de-an/theme.css` (dòng 2); phạm vi `.sbut-public` |
| **bandean-v2** | `bandean-v2` | xanh lá `#075831` → `#008A52` → vàng `#F3B23A`, cam `#EC5C02` | **đang dùng cho đăng nhập và toàn bộ website** | đăng nhập: `Modules/CmsFrontend/resources/views/auth/designs/bandean-v2/auth.css` (biến `--bd2-*`); đầu trang, banner hero, menu: `.../css/screens/bandean-v2/banner.css` (biến `--bd2-*`, thêm kem `#FCF9EC`, `#FDFBF6`, `#FBF7E7`, lime `#D4F380` lấy từ thiết kế); toàn bộ website: biến thương hiệu ở dòng 2 của `.../css/screens/bandean-v2/theme.css` và `.../css/screens/bandean-v2/palette.css` (màu xanh dương của `shared/campus-base.css` xoay sang xanh lá, tạo tự động) |

Ghi chú tên: bộ màu mới được đặt cho `bandean-v2` (theme và dự án riêng, tạo từ bản sao độc lập của `ban-de-an`; đổi màu ở đó không ảnh hưởng `ban-de-an`). Nếu thiết kế mới dành cho theme khác thì chỉ cần đổi tên ở mục 3 và dòng cuối của bảng trên.

## 1. Bộ màu `default` (Khuyến nông): xanh lá và đỏ

| Biến | Màu | Dùng cho |
|---|---|---|
| `--cms-brand` | `#006B43` | màu thương hiệu: liên kết, biểu tượng, chữ nhấn |
| `--cms-brand-dark` | `#004D31` | bản đậm của màu thương hiệu |
| `--cms-accent` | `#E81920` | điểm nhấn (đỏ) |
| `--cms-ink` | `#173C2E` | tiêu đề |
| `--cms-text` | `#26342D` | chữ nội dung |
| `--cms-muted` | `#687582` | chữ phụ |
| `--cms-line` | `#E0E8E3` | đường viền, đường kẻ |
| `--cms-surface` | `#F4F8F3` | nền vùng phụ |
| `--cms-paper` | `#FFFFFF` | nền thẻ, nền trang |
| `--cms-feature` | `#FFFDF2` | nền khối nổi bật (kem nhạt) |
| `--cms-band-white` | `#FFFFFF` | nền dải xen kẽ trang chủ |
| `--cms-band-green` | `#F3F8F1` | nền dải xen kẽ trang chủ (xanh nhạt) |
| `--cms-band-cream` | `#FFF9EC` | nền dải xen kẽ trang chủ (kem) |
| `--cms-band-line` | `#DFE9DC` | viền dải |

Khác: `--cms-radius: 5px`, font `'Roboto','Helvetica Neue',Arial,sans-serif`.

## 2. Bộ màu `nam-binh-duong` và `ban-de-an`: xanh navy, xanh lam và vàng đồng

Hai bộ màu hiện giống hệt nhau (đã so từng ký tự), mỗi theme giữ một bản riêng trong CSS của mình, nên sửa bộ này không ảnh hưởng bộ kia:

| Biến | Màu | Dùng cho |
|---|---|---|
| `--brand-primary` | `#102A43` | xanh navy chính: thanh trên, nền tối |
| `--brand-primary-hover` | `#1F4E6D` | bản sáng hơn của màu chính (giữa dải chuyển sắc) |
| `--brand-secondary` | `#39789A` | xanh lam: nhãn mục, đường nhấn dưới tiêu đề |
| `--brand-secondary-light` | `#65A9C7` | xanh lam nhạt |
| `--brand-secondary-soft` | `#DCECF3` | nền xanh rất nhạt |
| `--brand-accent` | `#C9943E` | vàng đồng: điểm nhấn |
| `--brand-accent-light` | `#E7BE6A` | vàng nhạt |
| `--brand-highlight` | `#D96B32` | cam: nhấn mạnh, cảnh báo nhẹ |
| `--background-main` | `#F4F7F8` | nền trang |
| `--background-warm` | `#F7F1E7` | nền ấm (kem) |
| `--background-card` | `#FFFFFF` | nền thẻ |
| `--text-primary` | `#13252F` | chữ chính |
| `--text-secondary` | `#60747E` | chữ phụ |
| `--text-on-dark` | `#FFFFFF` | chữ trên nền tối |
| `--text-on-accent` | `#102A43` | chữ trên nền vàng đồng |
| `--border-color` | `#CBD8DE` | viền |
| `--divider-color` | `#DCE5E9` | đường kẻ |
| `--overlay-color` | `rgba(16, 42, 67, .72)` | lớp phủ trên ảnh |
| `--shadow-color` | `rgba(16, 42, 67, .14)` | đổ bóng |
| `--brand-gradient` | `linear-gradient(135deg, #102A43 0%, #1F4E6D 52%, #39789A 100%)` | nền chuyển sắc |

Riêng `ban-de-an` và `bandean-v2` còn một lớp biến theo trang đề án (`.proposal-public`), chỉ là tên khác trỏ vào bảng trên: `--proposal-green` và `--proposal-dark` là `--brand-primary`; `--proposal-gold` là `--brand-accent`; `--proposal-cream` là `--background-warm`; `--proposal-ink` là `--text-primary`; `--proposal-muted` là `--text-secondary`. Đổi màu thì sửa ở bảng gốc, không sửa lớp tên này. Ngoài ra `ban-de-an/theme.css` còn các xám/xanh xám ghi cứng ở chỗ dùng (nhiều nhất `#DFE3E5`, `#E0E4E6`, `#A9B6BE`); chưa gom thành biến.

## 3. Bộ màu `bandean-v2`: xanh lá đậm, dải chuyển sắc xanh sang vàng, cam

Lấy từ ảnh bảng màu của bản thiết kế mới (12/11/2026). **Đã dùng cho giao diện đăng nhập `bandean-v2`** (`auth/designs/bandean-v2/auth.css`: `--bd2-green-deep`, `--bd2-green`, `--bd2-gold`, `--bd2-glow-green`, `--bd2-glow-gold`). **Website của theme cũng dùng bộ này** qua biến thương hiệu ở dòng 2 của `.../css/screens/bandean-v2/theme.css` (`--brand-primary` = `#075831`, `--brand-secondary` = `#008A52`, `--brand-accent` = `#F3B23A`, `--brand-highlight` = `#EC5C02`, nền kem `#FDFBF6`/`#FCF9EC`); ba tông phụ suy ra từ bộ màu: `--brand-primary-hover` `#0A6B3D`, `--brand-secondary-light` `#8CCAB1`, `--brand-secondary-soft` `#E6F3EE`.

Trong giao diện đăng nhập, nút và nhãn dùng `#075831`; chữ tiêu đề tô gradient `#075831` → `#008A52` → `#F3B23A` (đầu gradient để tràn 125% chiều dài chữ nên chữ cuối ngả vàng olive, không chạm hẳn `#F3B23A`); nền nửa kính dùng hai màu bóng đổ với độ trong suốt .26 (`#33FFAC`) và .42 (`#FFD88C`). Tên biến dưới đây là đề xuất, không phải tên có sẵn trong code.

| Biến đề xuất | Màu | Vai trò trong thiết kế |
|---|---|---|
| `--brand-primary` | `#075831` | xanh lá đậm: điểm đầu của dải chuyển sắc |
| `--brand-secondary` | `#008A52` | xanh lá: điểm giữa của dải chuyển sắc |
| `--brand-accent` | `#F3B23A` | vàng: điểm cuối của dải chuyển sắc |
| `--brand-highlight` | `#EC5C02` | cam: màu nhấn riêng, không nằm trong dải chuyển sắc |
| `--glow-green` | `#33FFAC` | bóng đổ và nền phai màu dần (xanh), chỉnh độ trong suốt cho phù hợp |
| `--glow-yellow` | `#FFD88C` | bóng đổ và nền phai màu dần (vàng), chỉnh độ trong suốt cho phù hợp |

**Dải chuyển sắc:** thứ tự từ trên xuống trong thiết kế là `#075831` → `#008A52` → `#F3B23A` (mũi tên "gradient" chỉ xuống). Hướng gradient (dọc hay chéo) và vị trí các điểm dừng thiết kế không ghi, cần hỏi nhà thiết kế trước khi viết CSS. Gợi ý khởi đầu, chưa xác nhận:

```css
--brand-gradient: linear-gradient(180deg, #075831 0%, #008A52 50%, #F3B23A 100%);
```

**Bóng đổ và nền phai:** `#33FFAC` và `#FFD88C` chỉ dùng với độ trong suốt thấp. Thiết kế không cho con số độ trong suốt; chọn khi dựng và ghi lại vào đây, ví dụ `rgba(51, 255, 172, .25)`.

**Tương phản đã đo (WCAG):**

| Cặp màu | Tỉ lệ | Kết quả |
|---|---|---|
| chữ trắng trên `#075831` | 8.57 | đạt |
| chữ trắng trên `#008A52` | 4.41 | **chưa đạt cho chữ thường** (cần 4.5); dùng cho chữ lớn, hoặc chữ đậm từ 18.7px, hoặc đổi sang `#075831` |
| chữ trắng trên `#EC5C02` | 3.44 | **chỉ dùng cho chữ lớn hoặc biểu tượng** |
| chữ trắng trên `#F3B23A` | 1.87 | **không dùng**; trên nền vàng dùng chữ tối |
| `#075831` trên `#F3B23A` | 4.59 | đạt (sát ngưỡng) |
| `#13252F` trên `#F3B23A` | 8.43 | đạt |
| `#075831` trên `#FFD88C` | 6.31 | đạt |
| `#008A52` trên nền trắng | 4.41 | **chưa đạt cho chữ thường** (dùng làm màu liên kết chữ nhỏ thì không đạt) |
| `#EC5C02` trên nền trắng | 3.44 | chỉ cho chữ lớn hoặc biểu tượng |

Hệ quả khi dựng: chữ thường nằm trên dải chuyển sắc cần kiểm tra theo từng điểm của dải, vì phần gần `#F3B23A` không đọc được bằng chữ trắng.

## 4. Tỉ lệ tương phản đã đo (WCAG, tính từ các mã màu ở mục 1 và 2)

Chữ thường cần từ 4.5 trở lên, chữ lớn (từ 18.7px đậm hoặc 24px) từ 3 trở lên.

| Cặp màu | Tỉ lệ | Kết quả |
|---|---|---|
| NBD/BDA `--text-primary` trên `--background-main` | 14.63 | đạt |
| NBD/BDA `--text-primary` trên `--background-card` | 15.75 | đạt |
| NBD/BDA `--text-secondary` trên `--background-card` | 4.89 | đạt (sát ngưỡng) |
| NBD/BDA chữ trắng trên `--brand-primary` | 14.64 | đạt |
| NBD/BDA `--text-on-accent` trên `--brand-accent` | 5.43 | đạt |
| NBD/BDA chữ trắng trên `--brand-secondary` | 4.85 | đạt (sát ngưỡng) |
| NBD/BDA chữ trắng trên `--brand-highlight` | 3.44 | **chỉ dùng cho chữ lớn hoặc biểu tượng** |
| default `--cms-text` trên `--cms-surface` | 12.14 | đạt |
| default `--cms-text` trên `--cms-paper` | 13.03 | đạt |
| default `--cms-muted` trên `--cms-paper` | 4.71 | đạt (sát ngưỡng) |
| default chữ trắng trên `--cms-brand` | 6.60 | đạt |
| default chữ trắng trên `--cms-accent` | 4.57 | đạt (sát ngưỡng) |

## 5. Khi thêm theme hoặc đổi màu

- Khai báo màu thành biến ở một chỗ đầu tệp CSS của theme, các chỗ còn lại dùng `var(...)`; không ghi mã màu cứng lặp lại.
- Màu mới cho chữ phải kiểm tra tương phản với nền đúng như bảng ở mục 4 và ghi thêm dòng vào bảng đó.
- Màu thuộc về theme, không thuộc về giao diện đăng nhập: giao diện đăng nhập (`auth/designs/*/auth.css`) không đọc các biến ở trên mà ghi mã màu riêng trong tệp của nó (ví dụ `ban-de-an` dùng `#102A43`, `#1F4E6D`, `#0D0D0D`, `#F7F7F5`), nên đổi màu theme không làm đổi trang đăng nhập và ngược lại.

## 6. Bộ font và cỡ chữ của `bandean-v2`

**Họ chữ:** Roboto, khai báo `'Roboto', 'Helvetica Neue', Arial, sans-serif`. Dùng tệp biến thiên cục bộ `Modules/CmsFrontend/resources/assets/fonts/roboto/Roboto-Variable-vi.woff2` (83 KB, giấy phép OFL, đủ ký tự tiếng Việt) khai trong `css/screens/bandean-v2/banner.css` với đủ độ đậm 100-900. Theme **không** nạp Google Fonts (đã bỏ ba dòng `<link>` Google trong `commonMaster.blade.php` của `bandean-v2`), nên độ đậm 800 của tiêu đề là độ đậm thật và trang không phụ thuộc mạng ngoài. Trang đăng nhập khai cùng tệp font trong `auth.css`.

**Độ đậm đang dùng:** 400 (nội dung), 500 (nhãn, nút viền), 600-700 (nút, tên liên kết, số thứ tự), 800 (tiêu đề cụm, tiêu đề thẻ, chữ lớn của banner).

**Cỡ chữ tối thiểu của trang: 16,8px trên máy tính, 15,6px trên điện thoại.** Đó là cỡ của dòng thông tin tuyển sinh ("Tìm hiểu chương trình đào tạo và thông tin dành cho thí sinh.") do người thiết kế chọn làm chuẩn: 14px × hệ số chữ 1,2 của trang chủ (điện thoại 13px × 1,2). Không chữ nào trên website (nhãn, ngày tháng, chú thích, nút, menu, chân trang) được nhỏ hơn mức này; kiểm tra thực tế bằng cách quét mọi nút chữ trên trang chủ: 0 chỗ nhỏ hơn ở cả hai kích thước màn hình.

| Vai trò | Cỡ chữ |
|---|---|
| Chữ nhỏ nhất (nhãn, ngày, chú thích, nút, menu, chân trang) | 16,8px (điện thoại 15,6px) = `--bd2-fs-min` |
| Nội dung, đoạn mô tả | 16,8 - 18px |
| Tiêu đề thẻ (h3) | `clamp(1.1rem, 1.7vw, 1.5rem)` và các cỡ tương tự, ít nhất 17-24px |
| Tiêu đề cụm (h2) | `clamp(1.9rem, 3.6vw, 3rem)` = 30 - 48px |
| Tiêu đề banner (h1) | `clamp(2rem, 3.8vw, 3.6rem)` = 32 - 58px |
| Con số nổi bật | `clamp(1.9rem, 3.4vw, 2.9rem)` |

**Cách ép mức tối thiểu:** biến `--bd2-fs-min` khai ở đầu `banner.css` (16,8px; 15,6px khi màn hình ≤ 760px). Mọi `font-size` trong `campus-base.css`, `theme.css`, `sections.css` và `banner.css` được bọc `max(giá-trị-gốc, var(--bd2-fs-min))` trong tệp **tạo tự động** `css/screens/bandean-v2/typography.css` (nạp sau cùng) bằng lệnh `node scripts/bandean-v2-derived-css.mjs` (kiểm tra có lệch nguồn không: thêm `--check`). Lệnh này cũng tạo `palette.css`. Muốn đổi mức tối thiểu của cả trang thì sửa đúng hai con số ở `banner.css`, không cần chạy lại lệnh. Khi thêm hoặc sửa bất kỳ `font-size` nào trong bốn tệp nguồn, chạy lại lệnh.

**Hai trường hợp đặc biệt đã xử lý:**
- Luật `#id` của `theme.css` (đặc hiệu hơn tiền tố) nhận thêm `!important` trong bản ép.
- Luật cũ của khung (`@media (max-width: 480px) { #mobileSidebar a, button { font-size: 13px !important } }`) ép mọi nút xuống 13px trên điện thoại; bản ép trả nút về cỡ chữ kế thừa từ khối cha, không nhỏ hơn mức tối thiểu.

**Ngoài phạm vi:** trang đăng nhập (`auth/designs/bandean-v2/auth.css`) và trang quản trị chưa áp mức tối thiểu này; hiện cỡ chữ nhỏ nhất ở đó khoảng 12-13px.

