<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Backend Breadcrumbs
|--------------------------------------------------------------------------
| File này định nghĩa breadcrumbs cho bảng: {tableName}.
*/

// Trang chủ
require __DIR__ . '/breadcrumbs/trang-chu.php';
// Danh mục
// require __DIR__ . '/breadcrumbs/danh-muc.php';

// Breadcrumbs cho từng page/bảng
require __DIR__ . '/breadcrumbs/TinTucBreadcrumb.php';
require __DIR__ . '/breadcrumbs/SuKienBreadcrumb.php';

require __DIR__ . '/breadcrumbs/LienHeBreadcrumb.php';
require __DIR__ . '/breadcrumbs/HoiDapBreadcrumb.php';
require __DIR__ . '/breadcrumbs/VanBanBreadcrumb.php';
require __DIR__ . '/breadcrumbs/HinhAnhBreadcrumb.php';
require __DIR__ . '/breadcrumbs/VideoBreadcrumb.php';
