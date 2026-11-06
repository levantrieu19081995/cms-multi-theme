<?php

namespace App\Http\Middleware;

use App\Services\LocaleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;

class LocaleMiddleware
{
  /**
   * Public pages take their locale from the URL only (default locale when the URL has no locale prefix), so a
   * Vietnamese URL always renders Vietnamese. Administration and other shared pages keep using the locale the
   * user picked (session, then cookie).
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next): Response
  {
    $locales = app(LocaleManager::class);
    $urlLocale = $locales->fromRequest($request);

    if ($urlLocale !== null) {
      $locale = $urlLocale;
    } elseif ($locales->isPublicRequest($request)) {
      $locale = $locales->default();
    } elseif (! $locales->multilingualEnabled()) {
      // Đa ngôn ngữ đã tắt: quản trị cũng chỉ dùng ngôn ngữ mặc định, dù session/cookie còn nhớ lựa chọn cũ.
      $locale = $locales->default();
    } else {
      $locale = Session::get('locale', Cookie::get('locale', config('app.locale')));
    }

    App::setLocale($locale);

    // Remembered for the language switcher / first-visit hints; never used to resolve a public URL.
    if ($locales->isPublicRequest($request) && $request->hasSession()) {
      Session::put('public_locale', $locale);
    }

    return $next($request);
  }
}
