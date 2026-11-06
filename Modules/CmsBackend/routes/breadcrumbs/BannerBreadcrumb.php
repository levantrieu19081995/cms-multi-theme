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
Breadcrumbs::for("cms.dashboard.cdn.banner.index", function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    // $trail->push("Quản lý Banner");
    $trail->push(__('admin.menu.quan_ly_banner'), \Modules\CmsBackend\Support\ModuleUrl::route("cms.dashboard.cdn.banner.index"));
});

Breadcrumbs::for("cms.dashboard.cdn.banner.create", function (BreadcrumbTrail $trail) {
    $trail->parent("cms.dashboard.cdn.banner.index");
    $trail->push(__('admin.breadcrumb.them_moi_banner'), \Modules\CmsBackend\Support\ModuleUrl::route("cms.dashboard.cdn.banner.create"));
});

/**
 * Nếu muốn hiển thị tên, thông tin data cần cập nhật thì truyền thêm thuộc tính: $data->'thuộc tính'
 *  Breadcrumbs::for("cms.cdn.banner.edit", function (BreadcrumbTrail $trail, $data) {
 *  $trail->parent("cdn.banner.index");
 * $trail->push("Cập nhật banner", \Modules\CmsBackend\Support\ModuleUrl::route('cms.cdn.banner.edit', $data));
 *});
 */

Breadcrumbs::for("cms.dashboard.cdn.banner.edit", function (BreadcrumbTrail $trail, $banner) {
    $trail->parent("cms.dashboard.cdn.banner.index");
    $trail->push(__('admin.breadcrumb.cap_nhat_banner'), \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.cdn.banner.edit', $banner->id));
});
