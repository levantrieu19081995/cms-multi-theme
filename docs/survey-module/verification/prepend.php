<?php
// Làm AppServiceProvider bỏ qua bộ đếm lượt truy cập khi chạy kiểm tra bằng CLI.
$_SERVER['HTTP_USER_AGENT'] = 'verification-bot';
