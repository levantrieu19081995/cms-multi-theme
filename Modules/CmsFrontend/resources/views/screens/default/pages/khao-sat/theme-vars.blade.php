{{--
    Biến trình bày của trang khảo sát (màu, chữ, kiểu hiển thị tiến độ). Mỗi theme chỉ cần ghi đè FILE NÀY
    (screens/<theme>/pages/khao-sat/theme-vars.blade.php) để đổi diện mạo; nghiệp vụ và biểu mẫu dùng chung. Thiếu file → dùng bản này.
    Kiểu tiến độ qua thẻ <meta name="sv-progress"> bên dưới: bar (thanh) | steps (các đoạn theo bước).
--}}
<meta name="sv-progress" content="bar">
<style>
    :root {
        --sv-primary: #2e7d32;
        --sv-primary-dark: #1b5e20;
        --sv-accent: #f9a825;
        --sv-bg: #f4f7f4;
        --sv-card: #ffffff;
        --sv-text: #1d2b1f;
        --sv-muted: #5f6f61;
        --sv-border: #d3ded4;
        --sv-danger: #b3261e;
        --sv-radius: 10px;
        --sv-font: system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
    }
</style>
