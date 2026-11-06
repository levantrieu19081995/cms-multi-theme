<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

/**
 * Trang chủ quản trị
 */

Breadcrumbs::for('cms.danh-muc', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push(__('admin.breadcrumb.danh_muc'), \Modules\CmsBackend\Support\ModuleUrl::route('cms.danh-muc'));
});
