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
Breadcrumbs::for('cms.file.index', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push(__('admin.menu.quan_ly_tap_tin'), ModuleUrl::route('cms.file.index'));
});
