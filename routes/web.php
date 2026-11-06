<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdsController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ImageLibraryController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LayoutController;
use App\Http\Controllers\LinksWebsiteController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

// Administration (framework screens). Every administration address lives under the /cms prefix (CMS_ROUTE_PREFIX); the route
// names are the ones the screens have always used (users.*, roles.*, menus.*, ...), only the addresses changed.
$cmsPrefix = trim((string) config('cmsbackend.route_prefix', 'cms'), '/');

// The shared dashboard address leads to the administration overview.
Route::redirect('/dashboard', '/'.$cmsPrefix)->name('dashboard');

Route::prefix($cmsPrefix)->middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Users
    Route::resource('/users', UserController::class);

    // Roles
    Route::resource('/roles', RoleController::class);

    // Menu
    Route::resource('/menus', MenuController::class);
    Route::get('/menus/{menu}/config', [MenuController::class, 'config'])->name('menus.config');

    Route::get('/setting/general', [SettingController::class, 'index'])->name('setting.general');
    Route::put('/setting/general', [SettingController::class, 'update'])->name('setting.general.update');
    Route::get('/setting/clear-cache', [SettingController::class, 'clearCache'])->name('setting.general.clear-cache');

    Route::post('/api/update-homepage', [PostController::class, 'changeShowHomePage'])->name('api.update-homepage');
    Route::post('/api/update-commentpage', [PostController::class, 'changeAllowCommentPage'])->name('api.update-commentpage');

    // Chuyên mục
    Route::resource('/chuyen-muc', CategoryController::class);

    // Bình luận  ->  /cms/binh-luan
    Route::get('/binh-luan', [CommentController::class, 'index'])->name('binh-luan.index');
    Route::put('/binh-luan/{id}/approve', [CommentController::class, 'approve'])->name('binh-luan.approve');
    Route::put('/binh-luan/{id}/reject', [CommentController::class, 'reject'])->name('binh-luan.reject');
    Route::put('/binh-luan/{id}/reply', [CommentController::class, 'reply'])->name('binh-luan.reply');
    Route::delete('/binh-luan/{id}', [CommentController::class, 'destroy'])->name('binh-luan.destroy');

    // Hình ảnh
    Route::resource('/hinh-anh', ImageLibraryController::class);

    // video
    Route::resource('/video', VideoController::class);

    Route::get('/file', [FileController::class, 'index'])->name('file.index');

    // Quảng cao
    Route::resource('/quang-cao', AdsController::class);

    // Log hành động
    Route::resource('activities', ActivityLogController::class);

    Route::get('/banner/view-banner/{postId}', [BannerController::class, 'viewBanner'])->name('banner.view-banner');

    if (app()->environment('local')) {
        Route::get('/components', function () {
            return view('pages.dev.components');
        })->name('dev.components');
    }
});

// Old shared-framework addresses of functions that the CmsBackend module serves at /cms/<function>: kept (not shown anywhere)
// so the old screens and their names keep working. Their GET pages redirect to the module (cms.editorial.legacy /
// cms.legacy.redirect); the module routes are the canonical ones and these are TEMPORARY aliases.
Route::middleware('auth')->group(function () {
    Route::resource('/bai-viet', PostController::class);
    Route::post('/bai-viet/{postId}/comments', [CommentController::class, 'store'])->name('binh-luan.store');
    Route::post('/bai-viet/{id}/approve', [PostController::class, 'approve'])->name('bai-viet.approve');
    Route::put('/bai-viet/{bai_viet}/submit', [PostController::class, 'submit'])->name('bai-viet.submit');
    Route::post('/bai-viet/{bai_viet}/reject', [PostController::class, 'reject'])->name('bai-viet.reject');
    Route::resource('/trang', PageController::class);
    Route::resource('/banner', BannerController::class);
    Route::resource('/lien-ket', LinksWebsiteController::class);
});

// Old GET addresses of the functions that moved under /cms: temporary redirects (writes go straight to the new address).
Route::get('/{area}/{rest?}', function (\Illuminate\Http\Request $request, string $area, ?string $rest = null) use ($cmsPrefix) {
    $query = $request->getQueryString();

    return redirect()->to(url('/'.$cmsPrefix.'/'.$area.($rest !== null && $rest !== '' ? '/'.$rest : '')).($query ? '?'.$query : ''), 302);
})->where('area', 'users|roles|menus|activities|binh-luan|chuyen-muc|hinh-anh|video|file|quang-cao|profile|page-builder|components')->where('rest', '.*');
Route::get('/setting/{page}', function (\Illuminate\Http\Request $request, string $page) use ($cmsPrefix) {
    $query = $request->getQueryString();

    return redirect()->to(url('/'.$cmsPrefix.'/setting/'.$page).($query ? '?'.$query : ''), 302);
})->where('page', 'general|clear-cache');

if (class_exists(LayoutController::class)) {
    Route::prefix($cmsPrefix)->group(function () {
        Route::get('/page-builder', [LayoutController::class, 'index'])->name('admin.page-builder');
        Route::post('/page-builder/save', [LayoutController::class, 'save'])->name('admin.page-builder.save');
    });
}
// Lang
Route::get('lang/{lang}', [LanguageController::class, 'switchLang'])->name('lang.switch');

Route::get('error/403', function () {
    return view('pages.errors.403');
})->name('pages.errors.403');

require __DIR__.'/auth.php';

require __DIR__.'/lfm.php';
require __DIR__.'/api.php';

// if (!Route::has('welcome')) {
//     Route::get('/', function () {
//         return redirect()->route('login');
//     })->name('welcome');
// }
