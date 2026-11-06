<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

// Danh sách video
Breadcrumbs::for('cms.video.danh-sach', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push('Thư viện video', \Modules\CmsBackend\Support\ModuleUrl::route('cms.video.danh-sach'));
});

// Chi tiết video  
Breadcrumbs::for('cms.video.show', function (BreadcrumbTrail $trail, $video) {
    $trail->parent('cms.video.danh-sach');
    $trail->push($video->title, \Modules\CmsBackend\Support\ModuleUrl::route('cms.video.show', $video->slug));
});
