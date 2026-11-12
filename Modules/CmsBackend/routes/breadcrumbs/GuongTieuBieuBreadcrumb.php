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
Breadcrumbs::for('cms.dashboard.guong-tieu-bieu.index', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push(__('admin.breadcrumb.danh_sach_tam_guong_tieu_bieu'), ModuleUrl::route('cms.dashboard.guong-tieu-bieu.index'));
});

Breadcrumbs::for('cms.dashboard.guong-tieu-bieu.create', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.dashboard.guong-tieu-bieu.index');
    $trail->push(__('admin.breadcrumb.them_moi_tam_guong_tieu_bieu'), ModuleUrl::route('cms.dashboard.guong-tieu-bieu.create'));
});

Breadcrumbs::for('cms.dashboard.guong-tieu-bieu.edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('cms.dashboard.guong-tieu-bieu.index');
    $trail->push(__('admin.breadcrumb.cap_nhat_tam_guong_tieu_bieu'), ModuleUrl::route('cms.dashboard.guong-tieu-bieu.edit', $id));
});
