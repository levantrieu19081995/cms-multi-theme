<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ImageLibraryController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LayoutController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;
use Modules\CmsBackend\Http\Controllers\ContentFlagsController;

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

    Route::post('/api/update-homepage', [ContentFlagsController::class, 'toggleShowHomePage'])->name('api.update-homepage');
    Route::post('/api/update-commentpage', [ContentFlagsController::class, 'toggleAllowComment'])->name('api.update-commentpage');

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

    if (app()->environment('local')) {
        Route::get('/components', function () {
            return view('pages.dev.components');
        })->name('dev.components');
    }
});

// Comments posted from an article page still use the shared address.
Route::middleware('auth')->group(function () {
    Route::post('/bai-viet/{postId}/comments', [CommentController::class, 'store'])->name('binh-luan.store');
});

// Old GET addresses of the functions that moved under /cms: temporary redirects (writes go straight to the new address).
Route::get('/{area}/{rest?}', function (\Illuminate\Http\Request $request, string $area, ?string $rest = null) use ($cmsPrefix) {
    $query = $request->getQueryString();

    return redirect()->to(url('/'.$cmsPrefix.'/'.$area.($rest !== null && $rest !== '' ? '/'.$rest : '')).($query ? '?'.$query : ''), 302);
})->where('area', 'users|roles|menus|activities|binh-luan|chuyen-muc|hinh-anh|video|file|quang-cao|profile|page-builder|components|bai-viet|trang|banner|lien-ket')->where('rest', '.*');
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
