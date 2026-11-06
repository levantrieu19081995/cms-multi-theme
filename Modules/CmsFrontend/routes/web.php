<?php

use Illuminate\Support\Facades\Route;
use Modules\CmsBackend\Http\Controllers\CmsBackendController;
use Modules\CmsFrontend\Http\Controllers\VanBanPublicController;
use Modules\CmsFrontend\Http\Controllers\CmsFrontendController;
use Modules\CmsFrontend\Http\Controllers\HomeController;
use Modules\CmsFrontend\Http\Controllers\SuKienFEController;
use Modules\CmsFrontend\Http\Controllers\TinTucController;
use Modules\CmsFrontend\Http\Controllers\LienHeController;
use Modules\CmsFrontend\Http\Controllers\HoiDapController;
use Modules\CmsFrontend\Http\Controllers\HoiDapPublicController;
use Modules\CmsBackend\Http\Controllers\SettingController;
use App\Http\Controllers\CommentController;
use Modules\CmsBackend\Http\Controllers\DoiTacController;
use Modules\CmsBackend\Http\Controllers\GuongTieuBieuController;
use Modules\CmsFrontend\Http\Controllers\DichVuPublicController;
use Modules\CmsFrontend\Http\Controllers\HinhAnhController;
use Modules\CmsFrontend\Http\Controllers\VideoController;
use Modules\CmsFrontend\Http\Controllers\LoginController;
use Modules\CmsFrontend\Http\Controllers\SocialImageController;

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

// Administration side of this module (under /cms): sign-in, sign-out, the home-page settings and the overview. The public
// website routes are in routes/public.php (root of the domain).
Route::group([], function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::post('/logout', [LoginController::class, 'destroy'])->middleware('cms.auth')->name('logout');

    Route::resource('cmsfrontend', CmsFrontendController::class)->names('cmsfrontend');
});

Route::middleware('cms.auth')->group(function () {
    Route::get('/', [CmsBackendController::class, 'index'])->name('dashboard');
    // Khảo sát (/cms/khao-sat): các route quản trị nằm trong Modules/CmsBackend/routes/web.php.
});

Route::middleware('cms.auth')->group(function () {
    Route::get('/setting/trang-chu', [SettingController::class, 'index'])->name('setting.trang-chu');
    Route::put('/setting/trang-chu', [SettingController::class, 'update'])->name('setting.trang-chu.update');

    // Keep old bookmarks working while all generated links use the neutral URL above.
    Route::get('/setting/cao-dang-nghe', fn () => redirect()->route('cms.setting.trang-chu'));
    Route::put('/setting/cao-dang-nghe', [SettingController::class, 'update']);
});

require 'api.php';
