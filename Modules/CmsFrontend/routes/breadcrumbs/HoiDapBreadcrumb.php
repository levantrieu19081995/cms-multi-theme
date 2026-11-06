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
Breadcrumbs::for("cms.hoi-dap", function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push("Hỏi đáp", \Modules\CmsBackend\Support\ModuleUrl::route("cms.frontend.hoidap.index"));
});
Breadcrumbs::for("cms.dat-cau-hoi", function (BreadcrumbTrail $trail) {
    $trail->parent('cms.hoi-dap');
    $trail->push("Đặt câu hỏi", \Modules\CmsBackend\Support\ModuleUrl::route("cms.frontend.hoidap.gui"));
});
Breadcrumbs::for('cms.hoi-dap.show', function (BreadcrumbTrail $trail, $id) {
    $trail->parent('cms.hoi-dap');
    $trail->push('Chi tiết hỏi đáp', \Modules\CmsBackend\Support\ModuleUrl::route('cms.frontend.hoidap.show', $id));
});