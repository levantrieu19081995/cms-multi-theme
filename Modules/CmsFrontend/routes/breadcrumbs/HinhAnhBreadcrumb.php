<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

// Danh sách thư viện hình ảnh
Breadcrumbs::for('cms.hinh-anh.danh-sach', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push('Thư viện hình ảnh', \Modules\CmsBackend\Support\ModuleUrl::route('cms.hinh-anh.danh-sach'));
});

// Chi tiết album hình ảnh
Breadcrumbs::for('cms.hinh-anh.show', function (BreadcrumbTrail $trail, $album) {
    $trail->parent('cms.hinh-anh.danh-sach');
    $trail->push($album->title, \Modules\CmsBackend\Support\ModuleUrl::route('cms.hinh-anh.show', $album->slug));
});
