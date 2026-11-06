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
require __DIR__ . '/breadcrumbs/BannerBreadcrumb.php';
require __DIR__ . '/breadcrumbs/SuKienBreadcrumb.php';
require __DIR__ . '/breadcrumbs/DichVuBreadcrumb.php';
require __DIR__ . '/breadcrumbs/BaiVietBreadcrumb.php';
require __DIR__ . '/breadcrumbs/VanBanBreadcrumb.php';
require __DIR__ . '/breadcrumbs/LoaiVanBanBreadcrumb.php';
require __DIR__ . '/breadcrumbs/NhomVanBanBreadcrumb.php';
require __DIR__ . '/breadcrumbs/HoiDapBreadcrumb.php';
require __DIR__ . '/breadcrumbs/LienHeBreadcrumb.php';
require __DIR__ . '/breadcrumbs/LienKetBreadcrumb.php';
require __DIR__ . '/breadcrumbs/TrangBreadcrumb.php';
require __DIR__ . '/breadcrumbs/ThuVienHinhAnhBreadcrumb.php';
require __DIR__ . '/breadcrumbs/VideoBreadcrumb.php';
require __DIR__ . '/breadcrumbs/DoiTacBreadcrumb.php';
require __DIR__ . '/breadcrumbs/VaiTroBreadcrumb.php';
require __DIR__ . '/breadcrumbs/MenuBreadcrumb.php';
require __DIR__ . '/breadcrumbs/UserBreadcrumb.php';
require __DIR__ . '/breadcrumbs/NhatKyBreadcrumb.php';
require __DIR__ . '/breadcrumbs/TapTinBreadcrumb.php';
require __DIR__ . '/breadcrumbs/CaiDatBreadcrumb.php';
require __DIR__ . '/breadcrumbs/GuongTieuBieuBreadcrumb.php';
require __DIR__ . '/breadcrumbs/MarqueeBreadcrumb.php';
require __DIR__ . '/breadcrumbs/TruyenThongBreadcrumb.php';