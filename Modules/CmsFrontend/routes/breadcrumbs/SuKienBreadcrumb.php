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
Breadcrumbs::for("cms.su-kien", function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push("Sự kiện", \Modules\CmsBackend\Support\ModuleUrl::route("cms.su-kien"));

});
Breadcrumbs::for('cms.su-kien.chi-tiet', function (BreadcrumbTrail $trail, $news) {
    $trail->parent('cms.su-kien');
    $trail->push('Chi tiết sự kiện', \Modules\CmsBackend\Support\ModuleUrl::route('cms.su-kien.chi-tiet', $news->slug ?? ''));
});


