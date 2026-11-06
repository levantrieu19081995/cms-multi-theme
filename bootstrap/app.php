<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LocaleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
  )
  ->withMiddleware(function (Middleware $middleware) {
    $middleware->web(LocaleMiddleware::class);
    // A visitor who is not signed in and asks for an administration address (/cms/...) goes to the CMS sign-in page.
    $middleware->redirectGuestsTo(fn ($request) => $request->is(trim((string) config('cmsbackend.route_prefix', 'cms'), '/'), trim((string) config('cmsbackend.route_prefix', 'cms'), '/').'/*') && \Illuminate\Support\Facades\Route::has('cms.login') ? route('cms.login') : route('login'));
  })
  ->withExceptions(function (Exceptions $exceptions) {
    //
  })->create();
