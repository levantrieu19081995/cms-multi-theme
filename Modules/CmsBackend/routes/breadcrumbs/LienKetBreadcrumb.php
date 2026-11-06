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
Breadcrumbs::for("cms.dashboard.cdn.lien-ket.index", function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push(__('admin.breadcrumb.quan_ly_lien_ket'), \Modules\CmsBackend\Support\ModuleUrl::route("cms.dashboard.cdn.lien-ket.index"));
});

Breadcrumbs::for("cms.dashboard.cdn.lien-ket.create", function (BreadcrumbTrail $trail) {
    $trail->parent("cms.dashboard.cdn.lien-ket.index");
    $trail->push(__('admin.breadcrumb.them_moi_lien_ket'), \Modules\CmsBackend\Support\ModuleUrl::route("cms.dashboard.cdn.lien-ket.create"));
});

/**
 * Nếu muốn hiển thị tên, thông tin data cần cập nhật thì truyền thêm thuộc tính: $data->'thuộc tính'
 *  Breadcrumbs::for("cms.cdn.banner.edit", function (BreadcrumbTrail $trail, $data) {
 *  $trail->parent("cdn.banner.index");
 * $trail->push("Cập nhật banner", \Modules\CmsBackend\Support\ModuleUrl::route('cms.cdn.banner.edit', $data));
 *});
 */

Breadcrumbs::for("cms.dashboard.cdn.lien-ket.edit", function (BreadcrumbTrail $trail) {
    $trail->parent("cms.dashboard.cdn.lien-ket.index");
    $trail->push(__('admin.breadcrumb.cap_nhat_lien_ket'));
});
