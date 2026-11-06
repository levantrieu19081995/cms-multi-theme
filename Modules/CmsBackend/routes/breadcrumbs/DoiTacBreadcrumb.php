<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

/*
|--------------------------------------------------------------------------
| Backend Breadcrumbs
|--------------------------------------------------------------------------
| File này định nghĩa breadcrumbs cho bảng: {tableName}.
*/

/**
 * Breadcrumbs cho bảng: {tableName}
 */
Breadcrumbs::for("cms.dashboard.doi-tac.index", function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push(__('admin.ui.danh_sach_doi_tac'), \Modules\CmsBackend\Support\ModuleUrl::route("cms.dashboard.doi-tac.index"));
});

Breadcrumbs::for("cms.dashboard.doi-tac.create", function (BreadcrumbTrail $trail) {
    $trail->parent("cms.dashboard.doi-tac.index");
    $trail->push(__('admin.ui.them_moi_doi_tac'), \Modules\CmsBackend\Support\ModuleUrl::route("cms.dashboard.doi-tac.create"));
});

Breadcrumbs::for("cms.dashboard.doi-tac.edit", function (BreadcrumbTrail $trail, $id) {
    $trail->parent("cms.dashboard.doi-tac.index");
    $trail->push(__('admin.ui.cap_nhat_doi_tac'), \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.doi-tac.edit', $id));
});
