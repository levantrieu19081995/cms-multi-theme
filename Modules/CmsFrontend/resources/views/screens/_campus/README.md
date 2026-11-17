# _campus: lớp view dùng chung của các theme campus

Không phải một theme (không chọn được trong Developer Settings). Chứa các view mà `nam-binh-duong` và `ban-de-an` có **giống hệt nhau**, để mỗi nội dung chỉ có một bản.

- Một view được tìm theo thứ tự: thư mục của theme, rồi `_campus` (nếu theme khai báo `'extends' => '_campus'` trong `Modules/CmsBackend/config/screens.php`), rồi `default`.
- Muốn một theme khác đi: chép tệp từ đây vào thư mục của theme đó và sửa; tệp của theme luôn thắng. Test `ScreenThemeLayerTest` báo nếu một tệp có mặt ở cả hai nơi (bản chép thừa).
- Tệp giống hệt `default` thì không đặt ở đây mà xóa khỏi theme (rơi về `default`).
- Khai báo build tương ứng: `layers` trong `scripts/theme-profiles.mjs` (kiểm tra `@vite` của build đọc cả lớp này).
