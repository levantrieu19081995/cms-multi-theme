# Tiêu chuẩn thiết kế giao diện

Áp dụng cho mọi theme công khai (`default`, `nam-binh-duong`, `ban-de-an`, `bandean-v2`, theme mới) và mọi giao diện đăng nhập (`auth/designs/*`). Tiêu chuẩn này chỉ nói về cách trình bày; nó không đổi dữ liệu, quyền hay luồng xử lý. Khi thiết kế mới có quy tắc riêng khác với tài liệu này, ghi rõ ngoại lệ vào `ghi-chu.txt` của bộ thiết kế và vào chú thích đầu tệp CSS.

## 1. Bo góc lồng nhau (nested radius)

**Công thức**

```
outer radius = inner radius + padding
```

Khi một khối bo góc nằm bên trong một khối bo góc khác (thẻ chứa ảnh, ô nhập trong thẻ đăng nhập, nút trong thanh tìm kiếm, nhãn trong thẻ tin...), hai đường cong phải đồng tâm. Khoảng cách giữa hai đường cong bằng đúng padding của khối ngoài thì mắt thấy khe đều quanh góc. Nếu dùng cùng một số bo cho cả hai, góc trong trông "dày" hơn ở chỗ góc và khe không đều.

Nếu khối ngoài có viền (`border`), cộng luôn độ dày viền:

```
outer radius = inner radius + padding + border
```

**Ví dụ**

| Khối trong | Padding khối ngoài | Bo góc khối ngoài |
|---|---|---|
| ảnh bo 8px | 12px | 20px |
| ô nhập bo 10px | 16px | 26px |
| nút bo 12px | 6px | 18px |
| ảnh bo 8px, khối ngoài có viền 1px, padding 12px | | 21px |

**Cách viết trong CSS: dùng biến, không ghi số cứng**

```css
.card {
  --pad: 12px;
  --r-inner: 8px;
  padding: var(--pad);
  border-radius: calc(var(--r-inner) + var(--pad));
}
.card__media {
  border-radius: var(--r-inner);
}
```

Đổi một trong hai biến thì cả hai góc tự khớp lại. Nhiều tầng thì tính từ trong ra ngoài: bo của tầng ngoài trở thành "inner" của tầng kế tiếp.

**Trường hợp đặc biệt**

- Khối trong không bo (`0`): khối ngoài bo bằng padding.
- Khối trong sát mép khối ngoài (padding = 0): hai khối dùng cùng một số bo và khối ngoài cắt phần tràn bằng `overflow: hidden`.
- Padding không đều các cạnh: lấy padding nhỏ nhất ở phía góc đang xét.
- Bo hình tròn hoàn toàn (`9999px` hoặc `50%`, ví dụ avatar, nút tròn): giữ nguyên, không áp dụng công thức.
- Khối ngoài bo quá lớn so với khối trong (padding rất lớn): chấp nhận theo công thức; nếu thiết kế cố ý khác, ghi là ngoại lệ.

**Kiểm tra khi nhận thiết kế**

1. Với mỗi cặp khối lồng nhau, đo bo góc hai khối và padding của khối ngoài.
2. Nếu outer khác inner + padding, hỏi nhà thiết kế hoặc ghi ngoại lệ, không tự đoán.
3. Trong CSS, hai giá trị nằm cạnh nhau và có quan hệ phải đi qua biến như trên.

## 2. Thẻ "tờ giấy - góc gập" (document card)

Theo hướng dẫn thiết kế (hình `Hướng dẫn tạo thẻ dạng "Tờ giấy - góc gập"`). Đang dùng ở cụm Văn bản của `bandean-v2` (`.bd2-doc`, `sections.css`).

| Thành phần | Giá trị |
|---|---|
| Bo góc thẻ | hướng dẫn 8 - 12px; **đang dùng 0 (ba góc vuông)** theo yêu cầu, góc còn lại là nếp gập |
| Nếp gập | tam giác vuông 20 - 28px theo hướng dẫn; **đang dùng 32px** (tăng theo yêu cầu để nếp gấp rõ hơn); biến thể nhỏ 20px, lớn 28px |
| Gáy xanh bên trái (`border-left`) | 4 - 8px (dùng 6px) |
| Bóng thẻ | `0 6px 18px rgba(0,0,0,.08)` |
| Bóng nếp gập | hướng dẫn `-2px 2px 6px rgba(0,0,0,.12)`; **đang dùng** hai lớp: bóng đổ xuống tờ giấy (lệch -3px 5px, làm mờ 6px, đen 40%, lan rộng và nhạt dần) và bóng phía nếp gập ngoài tờ giấy (một vệt mảnh đen 6% làm mờ 3px dọc cạnh chéo; không dùng khối tam giác vì có góc vuông) |
| Đệm (padding) | 20 - 28px |
| Chiều rộng tham khảo | 360px (lưới co giãn theo cột) |

Quy tắc: góc gập là một tam giác vuông; mặt gập trắng như giấy; mặt dưới có bóng mềm để có chiều sâu; bo góc thẻ theo hướng dẫn là 8 - 12px nhưng `bandean-v2` chọn góc vuông.

Cách làm trong CSS: cắt chéo góc thẻ bằng `clip-path` (cạnh cắt = kích thước nếp gập, đặt qua biến `--fold`), vẽ mặt gập bằng `::after` (tam giác trắng), bóng đổ xuống giấy bằng `::before` và bóng phía nếp gập bằng `li::after`. Bóng của `::before` phải vẽ tam giác bằng gradient cứng rồi `filter: blur` (không dùng `clip-path` cho nó, vì `clip-path` cắt sau khi làm mờ nên bóng không lan ra được). `clip-path` cắt mất `box-shadow` nên bóng của thẻ dùng `filter: drop-shadow` ở phần tử bao ngoài (blur của drop-shadow bằng một nửa blur của box-shadow: 18px thành 9px).

## 3. Bổ sung sau

Đây là mục đầu tiên của tiêu chuẩn thiết kế. Các quy tắc tiếp theo (khoảng cách, cỡ chữ, màu, trạng thái tương tác, ảnh) thêm vào dưới dạng mục đánh số mới, mỗi mục có công thức hoặc con số đo được và một ví dụ.
