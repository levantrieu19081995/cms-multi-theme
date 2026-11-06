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
Breadcrumbs::for("cms.video.index", function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push(__('admin.menu.quan_ly_video'), \Modules\CmsBackend\Support\ModuleUrl::route("cms.video.index"));
});

Breadcrumbs::for("cms.video.create", function (BreadcrumbTrail $trail) {
    $trail->parent("cms.video.index");
    $trail->push(__('admin.breadcrumb.them_moi_video'), \Modules\CmsBackend\Support\ModuleUrl::route("cms.video.create"));
});

/**
 * Nếu muốn hiển thị tên, thông tin data cần cập nhật thì truyền thêm thuộc tính: $data->'thuộc tính'
 *  Breadcrumbs::for("cms.cdn.banner.edit", function (BreadcrumbTrail $trail, $data) {
 *  $trail->parent("cdn.banner.index");
 * $trail->push("Cập nhật banner", \Modules\CmsBackend\Support\ModuleUrl::route('cms.cdn.banner.edit', $data));
 *});
 */

Breadcrumbs::for("cms.video.edit", function (BreadcrumbTrail $trail, $id) {
    $trail->parent("cms.video.index");
    $trail->push(__('admin.breadcrumb.cap_nhat_video'), \Modules\CmsBackend\Support\ModuleUrl::route('cms.video.edit', $id));
});
