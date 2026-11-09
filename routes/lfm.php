<?php

use App\Http\Middleware\CheckPermissionViewFolderShared;
use App\Http\Middleware\LfmRequireInput;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth', CheckPermissionViewFolderShared::class, LfmRequireInput::class]], function () {
  \UniSharp\LaravelFilemanager\Lfm::routes();
});
