<?php

use App\Http\Middleware\CheckPermissionViewFolderShared;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth',CheckPermissionViewFolderShared::class]], function () {
  \UniSharp\LaravelFilemanager\Lfm::routes();
});
