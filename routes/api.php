<?php

use Illuminate\Support\Facades\Route;
use Modules\CmsBackend\Http\Controllers\Api\RoleController as ApiRoleController;

// Role administration (/cms/api/vai-tro/...): same permission as the /cms/roles screen ("Quản lý vai trò", slug category-manager
// in the database).
Route::prefix(trim((string) config('cmsbackend.route_prefix', 'cms'), '/').'/api')->middleware(['auth', 'check.permission:'.\App\Enums\PermissionsEnum::ROLE_MANAGER->label()])->group(function () {
  Route::get('/vai-tro/getDsVaiTro', [ApiRoleController::class, 'getDsVaiTro'])->name('api.vai-tro.list');
  Route::get('/vai-tro/getDsVaiTroByUserId/{userId}', [ApiRoleController::class, 'getDsVaiTroByUserId'])->name('api.vai-tro.by-role');
  Route::post('/vai-tro/save/{userId?}', [ApiRoleController::class, 'save'])->name('api.vai-tro.save');
});
