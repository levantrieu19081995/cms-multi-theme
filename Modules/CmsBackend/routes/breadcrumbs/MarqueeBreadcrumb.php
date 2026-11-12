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
Breadcrumbs::for('cms.dashboard.cdn.marquee.index', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push(__('admin.menu.quan_ly_marquee'), ModuleUrl::route('cms.dashboard.cdn.marquee.index'));
});

Breadcrumbs::for('cms.dashboard.cdn.marquee.create', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.dashboard.cdn.marquee.index');
    $trail->push(__('admin.ui.them_moi_marquee'), ModuleUrl::route('cms.dashboard.cdn.marquee.create'));
});

/**
 * Nếu muốn hiển thị tên, thông tin data cần cập nhật thì truyền thêm thuộc tính: $data->'thuộc tính'
 *  Breadcrumbs::for("cms.cdn.banner.edit", function (BreadcrumbTrail $trail, $data) {
 *  $trail->parent("cdn.banner.index");
 * $trail->push("Cập nhật banner", \Modules\CmsBackend\Support\ModuleUrl::route('cms.cdn.banner.edit', $data));
 *});
 */
Breadcrumbs::for('cms.dashboard.cdn.marquee.edit', function (BreadcrumbTrail $trail, $post) {
    $trail->parent('cms.dashboard.cdn.marquee.index');
    $trail->push(__('admin.ui.cap_nhat_marquee'));
});
