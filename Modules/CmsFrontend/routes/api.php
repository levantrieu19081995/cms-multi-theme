<?php

use Illuminate\Support\Facades\Route;
use Modules\CmsBackend\Http\Controllers\CDNPostController;
use Modules\CmsFrontend\Http\Controllers\Api\CDNBannerController;
use Modules\CmsFrontend\Http\Controllers\HomeController;

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

Route::prefix('api')->name('api.')->group(function () {
    // Banner
    Route::get('/banners/getDsBanner', [CDNBannerController::class, 'getDsBanner'])->name('getDsBanner');
   Route::get('/posts/searchPostByName/{name}', [HomeController::class, 'searchPostByName'])
     ->name('searchPostByName');
});
