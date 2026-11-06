<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

/**
 * Trang chủ quản trị
 */
Breadcrumbs::for('cms.trang-chu', function (BreadcrumbTrail $trail) {
    $trail->push('Trang chủ', \Modules\CmsBackend\Support\ModuleUrl::route('cms.trang-chu'));
});
