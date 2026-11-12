<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Modules\CmsBackend\Support\ModuleUrl;

/*
|--------------------------------------------------------------------------
| Backend Breadcrumbs
|--------------------------------------------------------------------------
| File này định nghĩa breadcrumbs cho bảng: {tableName}.
*/

/**
 * Breadcrumbs cho bảng: {tableName}
 */
Breadcrumbs::for('cms.dashboard.cdn.thu-vien-hinh-anh.index', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push(__('admin.menu.quan_ly_thu_vien_hinh_anh'), ModuleUrl::route('cms.dashboard.cdn.thu-vien-hinh-anh.index'));
});

Breadcrumbs::for('cms.dashboard.cdn.thu-vien-hinh-anh.create', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.dashboard.cdn.thu-vien-hinh-anh.index');
    $trail->push(__('admin.breadcrumb.them_moi_thu_vien_hinh_anh'), ModuleUrl::route('cms.dashboard.cdn.thu-vien-hinh-anh.create'));
});

/**
 * Nếu muốn hiển thị tên, thông tin data cần cập nhật thì truyền thêm thuộc tính: $data->'thuộc tính'
 *  Breadcrumbs::for("cms.cdn.banner.edit", function (BreadcrumbTrail $trail, $data) {
 *  $trail->parent("cdn.banner.index");
 * $trail->push("Cập nhật banner", \Modules\CmsBackend\Support\ModuleUrl::route('cms.cdn.banner.edit', $data));
 *});
 */
Breadcrumbs::for('cms.dashboard.cdn.thu-vien-hinh-anh.edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('cms.dashboard.cdn.thu-vien-hinh-anh.index');
    $trail->push(__('admin.breadcrumb.cap_nhat_thu_vien_hinh_anh'), ModuleUrl::route('cms.dashboard.cdn.thu-vien-hinh-anh.edit', $id));
});
