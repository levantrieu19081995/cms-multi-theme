<?php

use Illuminate\Support\Facades\Route;
use Modules\CmsBackend\Http\Controllers\Api\RoleController;
use Modules\CmsBackend\Http\Controllers\Api\TinTucController;
use Modules\CmsBackend\Http\Controllers\CmsBackendController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('cmsbackend', CmsBackendController::class)->names('cmsbackend');
});
// Route::prefix('api')->name('api.')->group(function () {
//     // Vai trò
//     Route::get('/vai-tro/getDsVaiTro', [RoleController::class, 'getDsVaiTro']);
//     Route::get('/vai-tro/getDsVaiTroByUserId/{userId}', [RoleController::class, 'getDsVaiTroByUserId']);
//     Route::post('/vai-tro/save/{userId?}', [RoleController::class, 'save']);
//     // Banner (lấy thông tin bài viết)
//     Route::get('/bai-viet/getChiTietBaiViet/{postSlug}', [TinTucController::class, 'getChiTietBaiViet'])->name('getChiTietBaiViet');
// });
