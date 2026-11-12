<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Modules\CmsBackend\Support\ModuleUrl;

/**
 * Trang chủ quản trị
 */
Breadcrumbs::for('cms.dashboard', function (BreadcrumbTrail $trail) {
    $trail->push('Dashboard', ModuleUrl::route('Dashboard'));
});
