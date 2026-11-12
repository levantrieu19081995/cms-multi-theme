<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Http\Controllers\CDNBannerController;
use Modules\CmsBackend\Http\Controllers\CDNLinksWebsiteController;
use Modules\CmsBackend\Http\Controllers\CDNLoaiVanBanController;
use Modules\CmsBackend\Http\Controllers\CDNMarqueeController;
use Modules\CmsBackend\Http\Controllers\CDNNhomVanBanController;
use Modules\CmsBackend\Http\Controllers\CDNPostController;
use Modules\CmsBackend\Http\Controllers\CDNThuVienHinhAnhController;
use Modules\CmsBackend\Http\Controllers\CDNTrangController;
use Modules\CmsBackend\Http\Controllers\CDNVanBanController;
use Modules\CmsBackend\Http\Controllers\DeveloperAccountsController;
use Modules\CmsBackend\Http\Controllers\DeveloperSettingsController;
use Modules\CmsBackend\Http\Controllers\DichVuController;
use Modules\CmsBackend\Http\Controllers\DoiTacController;
use Modules\CmsBackend\Http\Controllers\FeaturedStatisticController;
use Modules\CmsBackend\Http\Controllers\GuongTieuBieuController;
use Modules\CmsBackend\Http\Controllers\HoiDapAdminController;
use Modules\CmsBackend\Http\Controllers\HomepageLayoutController;
use Modules\CmsBackend\Http\Controllers\KhaoSatController;
use Modules\CmsBackend\Http\Controllers\KhaoSatPhanHoiController;
use Modules\CmsBackend\Http\Controllers\KhaoSatThongKeController;
use Modules\CmsBackend\Http\Controllers\LienHeAdminController;
use Modules\CmsBackend\Http\Controllers\NgheNghiepCatalogController;
use Modules\CmsBackend\Http\Controllers\ScreenThemeController;
use Modules\CmsBackend\Http\Controllers\SocialAccountController;
use Modules\CmsBackend\Http\Controllers\SocialFacebookController;
use Modules\CmsBackend\Http\Controllers\SocialGuideController;
use Modules\CmsBackend\Http\Controllers\SocialOverviewController;
use Modules\CmsBackend\Http\Controllers\SocialPublicationController;
use Modules\CmsBackend\Http\Controllers\SocialStatisticsController;
use Modules\CmsBackend\Http\Controllers\SocialZaloController;
use Modules\CmsBackend\Http\Controllers\SuKienController;
use Modules\CmsBackend\Services\Social\SocialGuides;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['cms.developer.enabled'])->prefix('developer-settings')->group(function () {
    // Hidden developer area (5 clicks on a logo, or the address). Independent of the CMS sign-in: 404 without DEVELOPER_ACCESS_TOKEN_HASH, then only the Developer password + session flag.
    $dev = DeveloperSettingsController::class;
    Route::get('unlock', [$dev, 'unlockForm'])->name('dashboard.developer-settings.unlock');
    Route::post('unlock', [$dev, 'unlock'])->middleware('throttle:developer-unlock')->name('dashboard.developer-settings.unlock.store');
    Route::middleware('cms.developer')->group(function () use ($dev) {
        Route::get('/', [$dev, 'index'])->name('dashboard.developer-settings');
        Route::post('lock', [$dev, 'lock'])->name('dashboard.developer-settings.lock');
        Route::post('permissions', [$dev, 'saveAvailability'])->name('dashboard.developer-settings.permissions.save');
        Route::post('permissions/restore', [$dev, 'restoreAvailability'])->name('dashboard.developer-settings.permissions.restore');
        Route::post('theme', [$dev, 'saveTheme'])->middleware('throttle:10,1')->name('dashboard.developer-settings.theme.save');
        Route::post('features/{feature}', [$dev, 'saveFeature'])->where('feature', '[a-z0-9-]+')->middleware('throttle:10,1')->name('dashboard.developer-settings.features.save');
        // Tab "Tài khoản": đổi mật khẩu, khóa/mở, gán vai trò, cấu hình quyền của vai trò (chỉ phiên Developer).
        $accounts = DeveloperAccountsController::class;
        Route::prefix('accounts')->middleware('throttle:20,1')->group(function () use ($accounts) {
            Route::post('password', [$accounts, 'password'])->name('dashboard.developer-settings.accounts.password');
            Route::post('status', [$accounts, 'status'])->name('dashboard.developer-settings.accounts.status');
            Route::post('roles', [$accounts, 'roles'])->name('dashboard.developer-settings.accounts.roles');
            Route::post('edit-published', [$accounts, 'editPublished'])->name('dashboard.developer-settings.accounts.edit-published');
            Route::post('role-permissions', [$accounts, 'rolePermissions'])->name('dashboard.developer-settings.accounts.role-permissions');
        });
        // Homepage Layout Manager (tab "Bố cục trang chủ"): chỉ phiên Developer; preview là snapshot phía server, không đổi bố cục Live.
        $layout = HomepageLayoutController::class;
        Route::prefix('homepage-layout')->where(['theme' => '[a-z][a-z0-9-]*', 'token' => '[A-Za-z0-9]{40}'])->group(function () use ($layout) {
            Route::get('preview/{token}', [$layout, 'review'])->name('dashboard.developer-settings.homepage.review');
            Route::get('preview/{token}/frame', [$layout, 'frame'])->name('dashboard.developer-settings.homepage.frame');
            Route::post('preview/{token}/apply', [$layout, 'apply'])->middleware('throttle:10,1')->name('dashboard.developer-settings.homepage.apply');
            Route::post('{theme}/preview', [$layout, 'preview'])->middleware('throttle:20,1')->name('dashboard.developer-settings.homepage.preview');
            Route::post('{theme}/save', [$layout, 'save'])->middleware('throttle:10,1')->name('dashboard.developer-settings.homepage.save');
            Route::post('{theme}/install', [$layout, 'install'])->middleware('throttle:10,1')->name('dashboard.developer-settings.homepage.install');
            Route::post('{theme}/reset', [$layout, 'reset'])->middleware('throttle:10,1')->name('dashboard.developer-settings.homepage.reset');
        });
    });
});

Route::group([], function () {
    // Cài đặt thông tin trang chủ

    Route::middleware('cms.auth')->group(function () {
        Route::post('settings/themes', [ScreenThemeController::class, 'update'])->name('setting.themes.update');

        // URLs are /cms/<function> (no "dashboard", no technical "cdn" level); the route names keep their old
        // "dashboard." / "dashboard.cdn." parts so controllers, views, breadcrumbs and permissions are untouched.
        // The overview itself (/cms, cms.dashboard) and the home-page settings (/cms/setting/trang-chu) are defined once, in
        // the CmsFrontend module.
        // Hai cụm chức năng dùng chung bộ máy khảo sát, đăng ký từ MỘT khai báo (Support/SurveyCluster):
        //  - Khảo sát (/cms/khao-sat)                            : cms.feature:survey, quyền khaosat-*,   tên route cms.khao-sat.*
        //  - Định hướng nghề nghiệp (/cms/dinh-huong-nghe-nghiep): cms.feature:career, quyền nghenghiep-*, tên route cms.nghe-nghiep.*
        // Cổng chức năng trả 404 khi chức năng đang tắt trong Feature Registry; survey.cluster chuyển một bản ghi sang đúng cụm của nó
        // (khảo sát loại career chỉ thuộc cụm nghề nghiệp). Quyền theo từng hành động; POST thay cho PUT/DELETE vì máy chủ production
        // từ chối method-override.
        $surveyCluster = function (string $cluster) {
            $career = $cluster === 'career';
            $prefix = $career ? 'NGHENGHIEP' : 'KHAOSAT';
            $n = $career ? 'nghe-nghiep' : 'khao-sat';
            $p = PermissionsEnum::class;
            $can = fn (string ...$cases) => 'cms.check.permission:'.implode(',', array_map(fn (string $c) => constant($p.'::'.$prefix.'_'.$c)->label(), $cases));
            $survey = KhaoSatController::class;

            Route::prefix($career ? 'dinh-huong-nghe-nghiep' : 'khao-sat')->middleware([$career ? 'cms.feature:career' : 'cms.feature:survey', 'survey.cluster'])->group(function () use ($can, $survey, $n, $career) {
                // Danh sách là điểm vào chung: mọi quyền của cụm đều cần chọn được khảo sát; hành động trong danh sách vẫn theo từng quyền.
                Route::get('/', [$survey, 'index'])->middleware($can('VIEW', 'CREATE', 'UPDATE', 'PUBLISH', 'DELETE', 'RESPONSE_VIEW', 'RESPONSE_DELETE', 'STATISTICS_VIEW', 'EXPORT'))->name($n);
                Route::get('tao-moi', [$survey, 'create'])->middleware($can('CREATE'))->name($n.'.create');
                Route::post('/', [$survey, 'store'])->middleware($can('CREATE'))->name($n.'.store');
                Route::get('{survey}/chinh-sua', [$survey, 'edit'])->whereNumber('survey')->middleware($can('VIEW', 'UPDATE'))->name($n.'.edit');
                Route::post('{survey}/cap-nhat', [$survey, 'update'])->whereNumber('survey')->middleware($can('UPDATE'))->name($n.'.update');
                Route::post('{survey}/cau-hoi', [$survey, 'saveStructure'])->whereNumber('survey')->middleware($can('UPDATE'))->name($n.'.structure');
                Route::post('{survey}/trang-thai', [$survey, 'changeStatus'])->whereNumber('survey')->middleware($can('PUBLISH'))->name($n.'.status');
                Route::post('{survey}/nhan-ban', [$survey, 'duplicate'])->whereNumber('survey')->middleware($can('CREATE'))->name($n.'.duplicate');
                Route::get('{survey}/xem-truoc', [$survey, 'preview'])->whereNumber('survey')->middleware($can('VIEW', 'UPDATE'))->name($n.'.preview');
                Route::post('{survey}/xoa', [$survey, 'destroy'])->whereNumber('survey')->middleware($can('DELETE'))->name($n.'.destroy');
                Route::post('{survey}/khoi-phuc', [$survey, 'restore'])->whereNumber('survey')->withTrashed()->middleware($can('DELETE'))->name($n.'.restore');

                // Phản hồi, thống kê, xuất. Quyền xem của khảo sát (`*-view`) vẫn xem được danh sách/chi tiết phản hồi (tương thích).
                $responses = KhaoSatPhanHoiController::class;
                Route::get('{survey}/phan-hoi', [$responses, 'index'])->whereNumber('survey')->middleware($can('RESPONSE_VIEW', 'VIEW'))->name($n.'.responses');
                Route::get('luot/{participation}', [$responses, 'show'])->whereNumber('participation')->middleware($can('RESPONSE_VIEW', 'VIEW'))->name($n.'.show');
                Route::post('luot/{participation}/xoa', [$responses, 'destroy'])->whereNumber('participation')->middleware($can('RESPONSE_DELETE'))->name($n.'.responses.destroy');
                Route::get('{survey}/xuat', [$responses, 'export'])->whereNumber('survey')->middleware($can('EXPORT'))->name($n.'.export');
                Route::get('{survey}/thong-ke', [KhaoSatThongKeController::class, 'index'])->whereNumber('survey')->middleware($can('STATISTICS_VIEW'))->name($n.'.statistics');

                if ($career) {
                    // Chỉ cụm nghề nghiệp: tên/mô tả 6 nhóm RIASEC và ngành gợi ý do trường tự khai báo. Xem = xem/sửa, ghi = sửa.
                    $catalog = NgheNghiepCatalogController::class;
                    Route::get('nhom-nganh', [$catalog, 'index'])->middleware($can('VIEW', 'UPDATE'))->name($n.'.catalog');
                    Route::post('nhom-nganh/nhom', [$catalog, 'saveGroups'])->middleware($can('UPDATE'))->name($n.'.catalog.groups');
                    Route::post('nhom-nganh/nganh', [$catalog, 'storeMajor'])->middleware($can('UPDATE'))->name($n.'.catalog.major.store');
                    Route::post('nhom-nganh/nganh/{major}', [$catalog, 'updateMajor'])->whereNumber('major')->middleware($can('UPDATE'))->name($n.'.catalog.major.update');
                    Route::post('nhom-nganh/nganh/{major}/xoa', [$catalog, 'destroyMajor'])->whereNumber('major')->middleware($can('UPDATE'))->name($n.'.catalog.major.destroy');
                }
            });
        };
        $surveyCluster('survey');
        $surveyCluster('career');

        Route::name('dashboard.')->group(function () {
            Route::get('con-so-noi-bat', [FeaturedStatisticController::class, 'index'])->name('con-so-noi-bat.index');
            Route::put('con-so-noi-bat', [FeaturedStatisticController::class, 'update'])->name('con-so-noi-bat.update');
            Route::resource('hoi-dap', HoiDapAdminController::class)->only(['index', 'edit', 'update', 'destroy'])->names('hoi-dap');
            Route::put('hoi-dap/{id}/approve', [HoiDapAdminController::class, 'approve'])->name('hoi-dap.approve');
            // Liên hệ – Góp ý: chỉ xem / xóa mềm / khôi phục / xóa vĩnh viễn (quản trị không nhập liên hệ nên không có create/edit/update).
            // Cổng chức năng cms.feature:contact; quyền ở từng route (đã tính quyền cha lienhe-manager). POST vì production từ chối method-override.
            Route::prefix('lien-he')->middleware('cms.feature:contact')->name('lien-he.')->group(function () {
                $lienHe = LienHeAdminController::class;
                $view = 'cms.check.permission:'.PermissionsEnum::LIENHE_VIEW->label();
                $delete = 'cms.check.permission:'.PermissionsEnum::LIENHE_DELETE->label();
                Route::get('/', [$lienHe, 'index'])->middleware($view)->name('index');
                Route::get('{id}', [$lienHe, 'show'])->whereNumber('id')->middleware($view)->name('show');
                Route::post('{id}/xoa', [$lienHe, 'destroy'])->whereNumber('id')->middleware($delete)->name('destroy');
                Route::post('{id}/khoi-phuc', [$lienHe, 'restore'])->whereNumber('id')->middleware($delete)->name('restore');
                Route::post('{id}/xoa-vinh-vien', [$lienHe, 'purge'])->whereNumber('id')->middleware($delete)->name('purge');
            });
            Route::post('su-kien/{id}/hien-trang-chu', [SuKienController::class, 'showHome'])->whereNumber('id')->name('su-kien.show-home');
            Route::post('su-kien/{id}/purge', [SuKienController::class, 'purge'])->name('su-kien.purge');
            Route::resource('su-kien', SuKienController::class)->names('su-kien');
            Route::post('su-kien/{id}/reject', [SuKienController::class, 'reject'])->name('su-kien.reject');
            Route::resource('guong-tieu-bieu', GuongTieuBieuController::class)->names('guong-tieu-bieu');

            // Truyền thông đa kênh (Facebook / Zalo). Phase 1: preparation only, no call to a social platform.
            Route::prefix('truyen-thong')->name('truyen-thong.')->group(function () {
                Route::get('/', [SocialOverviewController::class, 'index'])->name('index');
                Route::get('tao-bai-dang', [SocialPublicationController::class, 'create'])->name('create');
                Route::post('tao-bai-dang', [SocialPublicationController::class, 'store'])->name('store');
                Route::post('tao-bai-dang/truc-tiep', [SocialPublicationController::class, 'storeDirect'])->name('store-direct');
                Route::get('bai-da-dang', [SocialPublicationController::class, 'index'])->name('publications.index');
                Route::get('bai-da-dang/{id}', [SocialPublicationController::class, 'show'])->whereNumber('id')->name('publications.show');
                Route::get('bai-da-dang/{id}/chinh-sua', [SocialPublicationController::class, 'edit'])->whereNumber('id')->name('publications.edit');
                // POST (not PUT): the production edge rejects method-override payloads.
                Route::post('bai-da-dang/{id}', [SocialPublicationController::class, 'update'])->whereNumber('id')->name('publications.update');
                Route::post('bai-da-dang/{id}/luu-tru', [SocialPublicationController::class, 'archive'])->whereNumber('id')->name('publications.archive');
                Route::post('bai-da-dang/{id}/khoi-phuc', [SocialPublicationController::class, 'restore'])->whereNumber('id')->name('publications.restore');
                // Send / send again / take down / read back. POST with CSRF; throttled because each one calls a social platform.
                Route::post('bai-da-dang/{id}/dang', [SocialPublicationController::class, 'publish'])->whereNumber('id')->middleware('throttle:10,1')->name('publications.publish');
                Route::post('bai-da-dang/{id}/dang-lai', [SocialPublicationController::class, 'retry'])->whereNumber('id')->middleware('throttle:10,1')->name('publications.retry');
                Route::post('bai-da-dang/{id}/go-bai', [SocialPublicationController::class, 'remove'])->whereNumber('id')->middleware('throttle:10,1')->name('publications.remove');
                Route::post('bai-da-dang/{id}/dong-bo', [SocialPublicationController::class, 'sync'])->whereNumber('id')->middleware('throttle:20,1')->name('publications.sync');
                Route::get('thong-ke', [SocialStatisticsController::class, 'index'])->name('statistics');
                Route::get('tai-khoan', [SocialAccountController::class, 'index'])->name('accounts');

                // Hướng dẫn: the .html files of Modules/CmsBackend/resources/guides/{chung|<project>}, each shown as it is. {scope} = chung | du-an (the current project).
                Route::get('huong-dan', [SocialGuideController::class, 'index'])->name('guides.index');
                Route::get('huong-dan/{scope}/{file}', [SocialGuideController::class, 'show'])->where(['scope' => SocialGuides::SCOPE_PATTERN, 'file' => SocialGuides::NAME_PATTERN])->name('guides.show');
                Route::get('huong-dan/{scope}/{file}/noi-dung', [SocialGuideController::class, 'raw'])->where(['scope' => SocialGuides::SCOPE_PATTERN, 'file' => SocialGuides::NAME_PATTERN])->name('guides.raw');

                // Kết nối Facebook Page (Phase 2A). Callback URL to register at Meta: .../cms/truyen-thong/tai-khoan/facebook/callback
                Route::prefix('tai-khoan/facebook')->name('facebook.')->group(function () {
                    Route::get('connect', [SocialFacebookController::class, 'connect'])->middleware('throttle:10,1')->name('connect');
                    Route::get('callback', [SocialFacebookController::class, 'callback'])->middleware('throttle:10,1')->name('callback');
                    Route::get('select-page', [SocialFacebookController::class, 'selectPage'])->name('select-page');
                    Route::post('confirm-page', [SocialFacebookController::class, 'confirmPage'])->middleware('throttle:10,1')->name('confirm-page');
                    Route::post('check', [SocialFacebookController::class, 'check'])->middleware('throttle:10,1')->name('check');
                    Route::post('disconnect', [SocialFacebookController::class, 'disconnect'])->name('disconnect');
                });

                // Kết nối Zalo OA. Callback URL to register in the Zalo app: .../cms/truyen-thong/tai-khoan/zalo/callback
                Route::prefix('tai-khoan/zalo')->name('zalo.')->group(function () {
                    Route::get('connect', [SocialZaloController::class, 'connect'])->middleware('throttle:10,1')->name('connect');
                    Route::get('callback', [SocialZaloController::class, 'callback'])->middleware('throttle:10,1')->name('callback');
                    Route::post('check', [SocialZaloController::class, 'check'])->middleware('throttle:10,1')->name('check');
                    Route::post('disconnect', [SocialZaloController::class, 'disconnect'])->name('disconnect');
                });
            });

            Route::resource('dich-vu', DichVuController::class)->names('dich-vu');
            Route::resource('doi-tac', DoiTacController::class)->names('doi-tac');

            Route::name('cdn.')->group(function () {
                Route::resource('banner', CDNBannerController::class)->names('banner');
                Route::resource('lien-ket', CDNLinksWebsiteController::class)->names('lien-ket');
                // Xuất file danh sách
                Route::get('bai-viet/export', [CDNPostController::class, 'export'])
                    ->name('bai-viet.export');

                // xem trước danh sách xuất
                Route::get('bai-viet/export/preview', [CDNPostController::class, 'exportPreview'])
                    ->name('bai-viet.export.preview');
                // The production edge rejects method-override payloads before they reach Laravel.
                // Keep the resource PUT route and provide a CSRF/auth protected POST equivalent for CMS forms.
                Route::post('bai-viet/{bai_viet}', [CDNPostController::class, 'update'])
                    ->name('bai-viet.update-post');
                Route::post('bai-viet/{id}/hien-trang-chu', [CDNPostController::class, 'showHome'])->whereNumber('id')->name('bai-viet.show-home');
                Route::post('bai-viet/{id}/purge', [CDNPostController::class, 'purge'])->name('bai-viet.purge');
                Route::resource('bai-viet', CDNPostController::class)->names('bai-viet');
                Route::post('bai-viet/{id}/reject', [CDNPostController::class, 'reject'])->name('bai-viet.reject');
                Route::resource('van-ban', CDNVanBanController::class)->names('van-ban');
                Route::resource('loai-van-ban', CDNLoaiVanBanController::class)->names('loai-van-ban');
                Route::resource('nhom-van-ban', CDNNhomVanBanController::class)->names('nhom-van-ban');
                Route::resource('marquee', CDNMarqueeController::class)->names('marquee');
                Route::put('marquee/{id}/approve', [CDNMarqueeController::class, 'approve'])->name('marquee.approve');
                Route::resource('trang', CDNTrangController::class)->names('trang');
                Route::resource('thu-vien-hinh-anh', CDNThuVienHinhAnhController::class)->names('thu-vien-hinh-anh');
            });
        });
    });

    // Cài đặt banner

});

require 'api.php';
