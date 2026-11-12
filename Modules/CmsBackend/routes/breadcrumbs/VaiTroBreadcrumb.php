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
Breadcrumbs::for('cms.roles.index', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push(__('admin.menu.vai_tro'), ModuleUrl::route('cms.roles.index'));
});

Breadcrumbs::for('cms.roles.create', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.roles.index');
    $trail->push(__('admin.breadcrumb.them_moi_vai_tro'), ModuleUrl::route('cms.roles.create'));
});

/**
 * Nếu muốn hiển thị tên, thông tin data cần cập nhật thì truyền thêm thuộc tính: $data->'thuộc tính'
 *  Breadcrumbs::for("cms.cdn.banner.edit", function (BreadcrumbTrail $trail, $data) {
 *  $trail->parent("cdn.banner.index");
 * $trail->push("Cập nhật banner", \Modules\CmsBackend\Support\ModuleUrl::route('cms.cdn.banner.edit', $data));
 *});
 */
Breadcrumbs::for('cms.roles.edit', function (BreadcrumbTrail $trail, $post) {
    $trail->parent('cms.roles.index');
    $trail->push(__('admin.breadcrumb.cap_nhat_vai_tro'));
});
