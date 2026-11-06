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
Breadcrumbs::for("cms.dashboard.su-kien.index", function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push(__('admin.breadcrumb.danh_sach_su_kien'), \Modules\CmsBackend\Support\ModuleUrl::route("cms.dashboard.su-kien.index"));
});

Breadcrumbs::for("cms.dashboard.su-kien.create", function (BreadcrumbTrail $trail) {
    $trail->parent("cms.dashboard.su-kien.index");
    $trail->push(__('admin.breadcrumb.them_moi_su_kien'), \Modules\CmsBackend\Support\ModuleUrl::route("cms.dashboard.su-kien.create"));
});

Breadcrumbs::for("cms.dashboard.su-kien.edit", function (BreadcrumbTrail $trail, $id) {
    $trail->parent("cms.dashboard.su-kien.index");
    $trail->push(__('admin.breadcrumb.cap_nhat_su_kien'), \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.su-kien.edit', $id));
});
