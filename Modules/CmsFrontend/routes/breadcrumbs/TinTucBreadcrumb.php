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
Breadcrumbs::for("cms.tin-tuc.index", function (BreadcrumbTrail $trail, $categoryInfo = null) {
    $trail->parent('cms.trang-chu');
    // $trail->push("Tin tức", \Modules\CmsBackend\Support\ModuleUrl::route("cms.tin-tuc.index"));

    if ($categoryInfo && $categoryInfo->term) {
        $trail->push(
            $categoryInfo->term->name ?? '',
            \Modules\CmsBackend\Support\ModuleUrl::route('cms.tin-tuc.index', $categoryInfo->term->slug ?? '')
        );
    }
});

Breadcrumbs::for('cms.tin-tuc.show', function (BreadcrumbTrail $trail, $news) {
    $trail->parent('cms.tin-tuc.index');
    $trail->push($news->title ?? '', \Modules\CmsBackend\Support\ModuleUrl::route('tin-tuc.show', $news->slug ?? ''));
});

Breadcrumbs::for('cms.tin-tuc.show-one-page', function (BreadcrumbTrail $trail, $news) {
    $trail->parent('cms.trang-chu');
    $trail->push($news->title ?? '', \Modules\CmsBackend\Support\ModuleUrl::route('tin-tuc.show', $news->slug ?? ''));
});
