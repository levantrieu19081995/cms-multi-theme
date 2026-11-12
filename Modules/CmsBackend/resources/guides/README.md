# Hướng dẫn (guides)

Các tệp `.html` trong thư mục này hiện ở nút **Hướng dẫn** của màn hình Truyền thông đa kênh (`/cms/truyen-thong/huong-dan`).

| Thư mục | Ai thấy |
|---|---|
| `chung/` | Mọi dự án |
| `nam-binh-duong/` | Chỉ dự án Nam Bình Dương |
| `ban-de-an/` | Chỉ dự án Ban Đề án |
| `khuyen-nong/` | Chỉ dự án Khuyến nông |

- Tên thư mục dự án phải trùng mã dự án trong `Modules/CmsBackend/config/projects.php`.
- Một dự án chỉ thấy `chung/` và thư mục của chính nó (dự án hiện hành lấy từ `CMS_PROJECT_KEY`), không bao giờ thấy thư mục dự án khác.
- Thêm hướng dẫn: bỏ một tệp `.html` vào đúng thư mục rồi deploy. Tên tài liệu hiển thị lấy từ thẻ `<title>` của tệp.
- Tên tệp chỉ gồm chữ, số, dấu chấm, gạch ngang, gạch dưới và kết thúc bằng `.html`. Tối đa 2 MB mỗi tệp.
- Tệp phải tự chứa (CSS và ảnh nhúng sẵn): tài liệu được hiển thị trong khung cô lập, không chạy script và không tải tệp phụ từ website.
- Nội dung dưới thư mục này nằm trong mã nguồn (kho git công khai): không đặt thông tin bí mật.
