<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionViewFolderShared
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Bạn chưa đăng nhập.');
        }
      
        if($user->hasPermissionTo(PermissionsEnum::SHARED_FOLDER_MANAGER->label())) {

             config(['lfm.allow_shared_folder' => true]);
        }
         return $next($request);
        //return response()->view('pages.errors.403', [], 403);
    }
}
