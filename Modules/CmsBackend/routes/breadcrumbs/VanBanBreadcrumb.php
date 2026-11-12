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
Breadcrumbs::for('cms.dashboard.cdn.van-ban.index', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push(__('admin.menu.quan_ly_van_ban'), ModuleUrl::route('cms.dashboard.cdn.van-ban.index'));
});

Breadcrumbs::for('cms.dashboard.cdn.van-ban.create', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.dashboard.cdn.van-ban.index');
    $trail->push(__('admin.breadcrumb.them_moi_van_ban'), ModuleUrl::route('cms.dashboard.cdn.van-ban.create'));
});

/**
 * Nếu muốn hiển thị tên, thông tin data cần cập nhật thì truyền thêm thuộc tính: $data->'thuộc tính'
 *  Breadcrumbs::for("cms.cdn.banner.edit", function (BreadcrumbTrail $trail, $data) {
 *  $trail->parent("cdn.banner.index");
 * $trail->push("Cập nhật banner", \Modules\CmsBackend\Support\ModuleUrl::route('cms.cdn.banner.edit', $data));
 *});
 */
Breadcrumbs::for('cms.dashboard.cdn.van-ban.edit', function (BreadcrumbTrail $trail, $post) {
    $trail->parent('cms.dashboard.cdn.van-ban.index');
    $trail->push(__('admin.breadcrumb.cap_nhat_van_ban'));
});
