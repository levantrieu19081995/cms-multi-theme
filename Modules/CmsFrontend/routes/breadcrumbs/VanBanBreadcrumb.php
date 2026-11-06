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
Breadcrumbs::for("cms.van-ban", function (BreadcrumbTrail $trail, $nvb = null) {
    $trail->parent('cms.trang-chu');
    $trail->push("Văn bản", \Modules\CmsBackend\Support\ModuleUrl::route("cms.frontend.vanban.index"));
    if ($nvb !== null) {
    $trail->push($nvb->nvb_name ?? '');
    }

});
Breadcrumbs::for('cms.van-ban.show', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('cms.van-ban');
    $trail->push('Chi tiết văn bản', \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.vanban.show', $id));
});
