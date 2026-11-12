<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Modules\CmsBackend\Support\ModuleUrl;

/**
 * Trang chủ quản trị
 */
Breadcrumbs::for('cms.danh-muc', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push(__('admin.breadcrumb.danh_muc'), ModuleUrl::route('cms.danh-muc'));
});
