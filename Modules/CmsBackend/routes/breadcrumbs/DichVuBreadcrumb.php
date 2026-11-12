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
Breadcrumbs::for('cms.dashboard.dich-vu.index', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push(__('admin.breadcrumb.quan_ly_dich_vu'));
    $trail->push(__('admin.breadcrumb.danh_sach_dich_vu'), ModuleUrl::route('cms.dashboard.dich-vu.index'));
});

Breadcrumbs::for('cms.dashboard.dich-vu.create', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.dashboard.dich-vu.index');
    $trail->push(__('admin.breadcrumb.them_moi_dich_vu'), ModuleUrl::route('cms.dashboard.dich-vu.create'));
});

Breadcrumbs::for('cms.dashboard.dich-vu.edit', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('cms.dashboard.dich-vu.index');
    $trail->push(__('admin.breadcrumb.cap_nhat_dich_vu'), ModuleUrl::route('cms.dashboard.dich-vu.edit', $id));
});
