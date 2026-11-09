<?php

use Illuminate\Support\Facades\Route;
use Modules\CmsFrontend\Http\Controllers\VanBanPublicController;
use Modules\CmsFrontend\Http\Controllers\HomeController;
use Modules\CmsFrontend\Http\Controllers\SuKienFEController;
use Modules\CmsFrontend\Http\Controllers\TinTucController;
use Modules\CmsFrontend\Http\Controllers\LienHeController;
use Modules\CmsBackend\Http\Controllers\Shared\CommentController;
use Modules\CmsBackend\Http\Controllers\DoiTacController;
use Modules\CmsBackend\Http\Controllers\GuongTieuBieuController;
use Modules\CmsFrontend\Http\Controllers\HoiDapPublicController;
use Modules\CmsFrontend\Http\Controllers\DichVuPublicController;
use Modules\CmsFrontend\Http\Controllers\HinhAnhController;
use Modules\CmsFrontend\Http\Controllers\VideoController;
use Modules\CmsFrontend\Http\Controllers\SocialImageController;
use Modules\CmsFrontend\Http\Controllers\KhaoSatPublicController;

/*
|--------------------------------------------------------------------------
| Public website routes
|--------------------------------------------------------------------------
| Served from the root of the domain (/, /tin-tuc, /gioi-thieu, /su-kien, ...), loaded by the CmsFrontend RouteServiceProvider
| with the names cms.* that every theme already uses. Other languages live under /{locale}/... (cms.{locale}.*).
| The administration is under /cms and is defined in routes/web.php of the two modules.
*/

// Pages whose content has translations (posts, categories, menu, texts of the site). Registered once for the default language
// and again, by the loop below, under {route_prefix}/{locale} with the names cms.{locale}.*  (e.g. /cms/en/news-title.htm).
// Content kept in tables of its own (services, Q&A, documents, partners, role models) has no translation table yet, so
// those pages exist for the default language only.
$localizedRoutes = function () {
    Route::get('/', [HomeController::class, 'index'])->name('trang-chu');
    Route::get('/su-kien', [SuKienFEController::class, 'index'])->name('su-kien');
    Route::get('su-kien/{slug}', [SuKienFEController::class, 'detail'])->name('su-kien.chi-tiet');

    // Danh sách hình ảnh (library index) and the pictures of one library
    Route::get('/thu-vien-hinh-anh', [HinhAnhController::class, 'danhSach'])
        ->name('hinh-anh.danh-sach');
    Route::get('/thu-vien-hinh-anh/{slug}', [HinhAnhController::class, 'showBySlug'])
        ->where('slug', '[a-zA-Z0-9-_]+')
        ->name('hinh-anh.show');

    Route::get('/{slug}.htm', [TinTucController::class, 'show'])->name('tin-tuc.show');
    Route::get('/tin-tuc/{category?}', [TinTucController::class, 'index'])
        ->where('category', '[a-zA-Z0-9-_]+')
        ->name('tin-tuc.index');
    Route::get('/yeu-cau-dang-nhap', [TinTucController::class, 'canAccessNoiBo'])->name('yeu-cau-dang-nhap');

    Route::post('/{slug}.htm', [CommentController::class, 'store'])->name('binh-luan-bai-viet.store');

    // Liên hệ – Góp ý: cổng chức năng cms.feature:contact (404 khi tắt); POST có CSRF của nhóm web + giới hạn 5 lần/10 phút/IP (contact-write).
    // Nằm trong $localizedRoutes nên /en/lien-he nhận cùng middleware.
    Route::get('/lien-he', [LienHeController::class, 'index'])->middleware('cms.feature:contact')->name('lien-he');
    Route::post('/lien-he', [LienHeController::class, 'store'])->middleware(['cms.feature:contact', 'throttle:contact-write'])->name('lien-he.store');

    // Video library
    Route::get('/thu-vien-video', [VideoController::class, 'danhSach'])
        ->name('video.danh-sach');
    Route::get('/thu-vien-video/{slug}', [VideoController::class, 'showBySlug'])
        ->where('slug', '[a-zA-Z0-9-_]+')
        ->name('video.show');
};

Route::group([], function () use ($localizedRoutes) {
    Route::get('/soc-trang', [HomeController::class, 'index']);
    Route::get('/social-image/{post}/preview.jpg', [SocialImageController::class, 'show'])
        ->whereNumber('post')
        ->name('social-image');
    Route::get('/social-image/guong-tieu-bieu/{id}/preview.jpg', [SocialImageController::class, 'showExample'])
        ->whereNumber('id')
        ->name('social-image.guong-tieu-bieu');

    // sitemap.xml: every language, only versions that exist (see SitemapController)
    Route::get('/sitemap.xml', [\Modules\CmsFrontend\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

    // Default language
    Route::group([], $localizedRoutes);

    // Default-language only (own-table content, no translation tables yet)
    Route::get('/hoi-dap', [HoiDapPublicController::class, 'index'])->name('frontend.hoidap.index');
    Route::get('/dich-vu', [DichVuPublicController::class, 'index'])->name('frontend.dichvu.index');
    Route::get('/hoi-dap/dat-cau-hoi', [HoiDapPublicController::class, 'gui'])->name('frontend.hoidap.gui');
    Route::post('/hoi-dap/gui', [HoiDapPublicController::class, 'store'])->name('frontend.hoidap.store');
    Route::get('/hoi-dap/{id}', [HoiDapPublicController::class, 'show'])->name('frontend.hoidap.show');
    Route::get('/van-ban/chi-tiet/{id}', [VanBanPublicController::class, 'show'])->name('frontend.vanban.show');
    Route::get('/van-ban/{slug?}', [VanBanPublicController::class, 'index'])->name('frontend.vanban.index');
    Route::get('/doi-tac/{slug}', [DoiTacController::class, 'showFront'])->name('doi-tac.show');
    Route::get('/guong-tieu-bieu/chi-tiet/{id}', [GuongTieuBieuController::class, 'showById'])->name('guong-tieu-bieu.detail-by-id');

    // Khảo sát công khai (/khao-sat/{mã}) và Định hướng nghề nghiệp công khai (/dinh-huong-nghe-nghiep/{mã}): CÙNG controller và view
    // (Support/SurveyCluster chọn tên route/chữ theo cụm). Mỗi cụm bật/tắt bằng chức năng riêng trong Feature Registry
    // (cms.feature:survey / cms.feature:career). POST bắt đầu/gửi bị giới hạn tốc độ (throttle:survey-write, RouteServiceProvider) và có CSRF.
    foreach (['khao-sat' => ['survey', 'frontend.khaosat.'], 'dinh-huong-nghe-nghiep' => ['career', 'frontend.nghenghiep.']] as $path => [$feature, $prefix]) {
        Route::prefix($path)->middleware('cms.feature:'.$feature)->name($prefix)->group(function () {
            $survey = KhaoSatPublicController::class;
            // Danh sách phải khai báo TRƯỚC /{slug}.
            Route::get('/', [$survey, 'index'])->middleware('throttle:survey-read')->name('index');
            Route::get('{slug}', [$survey, 'show'])->middleware('throttle:survey-read')->name('show');
            Route::post('{slug}/bat-dau', [$survey, 'start'])->middleware('throttle:survey-write')->name('start');
            Route::get('{slug}/lam-bai', [$survey, 'form'])->middleware('throttle:survey-read')->name('form');
            Route::post('{slug}/gui', [$survey, 'submit'])->middleware('throttle:survey-write')->name('submit');
            Route::get('{slug}/cam-on', [$survey, 'thanks'])->middleware('throttle:survey-read')->name('thanks');
        });
    }

    // Other languages: {route_prefix}/{locale}/...  (the prefix comes from CMS_ROUTE_PREFIX, never written here)
    // Đăng ký theo danh sách cấu hình; chức năng "multilingual" tắt thì cms.feature trả 404 cho các route này.
    foreach (app(\App\Services\LocaleManager::class)->configuredAlternates() as $locale) {
        Route::prefix($locale)->name($locale.'.')
            ->middleware(['cms.feature:multilingual', \Modules\CmsFrontend\Http\Middleware\ResolveLocalizedSlugs::class])
            ->group(function () use ($localizedRoutes) {
                $localizedRoutes();
                Route::get('/content-updating', [TinTucController::class, 'contentUpdating'])->name('content-updating');
            });
    }
});
