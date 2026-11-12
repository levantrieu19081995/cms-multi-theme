<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Modules\CmsBackend\Support\ModuleUrl;

/*
|--------------------------------------------------------------------------
| Truyền thông đa kênh
|--------------------------------------------------------------------------
*/

Breadcrumbs::for('cms.dashboard.truyen-thong.index', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.trang-chu');
    $trail->push(__('admin.social.title'), ModuleUrl::route('cms.dashboard.truyen-thong.index'));
});

Breadcrumbs::for('cms.dashboard.truyen-thong.create', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.dashboard.truyen-thong.index');
    $trail->push(__('admin.social.create_title'), ModuleUrl::route('cms.dashboard.truyen-thong.create'));
});

Breadcrumbs::for('cms.dashboard.truyen-thong.publications.index', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.dashboard.truyen-thong.index');
    $trail->push(__('admin.social.publications_title'), ModuleUrl::route('cms.dashboard.truyen-thong.publications.index'));
});

Breadcrumbs::for('cms.dashboard.truyen-thong.publications.show', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.dashboard.truyen-thong.publications.index');
    $trail->push(__('admin.social.detail_title'));
});

Breadcrumbs::for('cms.dashboard.truyen-thong.publications.edit', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.dashboard.truyen-thong.publications.index');
    $trail->push(__('admin.social.edit_title'));
});

Breadcrumbs::for('cms.dashboard.truyen-thong.accounts', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.dashboard.truyen-thong.index');
    $trail->push(__('admin.social.accounts_title'), ModuleUrl::route('cms.dashboard.truyen-thong.accounts'));
});

Breadcrumbs::for('cms.dashboard.truyen-thong.guides.index', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.dashboard.truyen-thong.index');
    $trail->push(__('admin.social.guides_title'), ModuleUrl::route('cms.dashboard.truyen-thong.guides.index'));
});

Breadcrumbs::for('cms.dashboard.truyen-thong.guides.show', function (BreadcrumbTrail $trail) {
    $trail->parent('cms.dashboard.truyen-thong.guides.index');
    $trail->push(__('admin.social.guide_detail_title'));
});
