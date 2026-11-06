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

/**
 * Breadcrumbs cho bảng: {tableName}
 */
Breadcrumbs::for("cms.users.index", function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push(__('admin.menu.nguoi_dung'), \Modules\CmsBackend\Support\ModuleUrl::route("cms.users.index"));
});

Breadcrumbs::for("cms.users.create", function (BreadcrumbTrail $trail) {
    $trail->parent("cms.users.index");
    $trail->push(__('admin.breadcrumb.them_moi_nguoi_dung'), \Modules\CmsBackend\Support\ModuleUrl::route("cms.users.create"));
});

/**
 * Nếu muốn hiển thị tên, thông tin data cần cập nhật thì truyền thêm thuộc tính: $data->'thuộc tính'
 *  Breadcrumbs::for("cms.cdn.banner.edit", function (BreadcrumbTrail $trail, $data) {
 *  $trail->parent("cdn.banner.index");
 * $trail->push("Cập nhật banner", \Modules\CmsBackend\Support\ModuleUrl::route('cms.cdn.banner.edit', $data));
 *});
 */

Breadcrumbs::for("cms.users.edit", function (BreadcrumbTrail $trail, $post) {
    $trail->parent("cms.users.index");
    $trail->push(__('admin.breadcrumb.cap_nhat_nguoi_dung'));
});
