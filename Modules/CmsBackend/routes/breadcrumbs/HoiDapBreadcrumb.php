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
Breadcrumbs::for("cms.dashboard.hoidap.index", function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push(__('admin.ui.quan_ly_hoi_dap'), \Modules\CmsBackend\Support\ModuleUrl::route("cms.dashboard.hoi-dap.index"));
});

// Breadcrumbs::for("cms.dashboard.hoidap.create", function (BreadcrumbTrail $trail) {
//     $trail->parent("cms.dashboard.hoidap.index");
//     $trail->push("Thêm mới hỏi đáp", \Modules\CmsBackend\Support\ModuleUrl::route("cms.dashboard.hoidap.create"));
// });

/**
 * Nếu muốn hiển thị tên, thông tin data cần cập nhật thì truyền thêm thuộc tính: $data->'thuộc tính'
 *  Breadcrumbs::for("cms.cdn.banner.edit", function (BreadcrumbTrail $trail, $data) {
 *  $trail->parent("cdn.banner.index");
 * $trail->push("Cập nhật banner", \Modules\CmsBackend\Support\ModuleUrl::route('cms.cdn.banner.edit', $data));
 *});
 */

Breadcrumbs::for("cms.dashboard.hoidap.edit", function (BreadcrumbTrail $trail, $post) {
    $trail->parent("cms.dashboard.hoidap.index");
    $trail->push(__('admin.ui.tra_loi_cau_hoi'));
});
