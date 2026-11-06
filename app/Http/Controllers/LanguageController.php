<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;

class LanguageController extends Controller
{
    public function switchLang($lang)
    {
        // Kiểm tra xem ngôn ngữ có hợp lệ hay không (có trong danh sách ngôn ngữ được hỗ trợ)
        if (app(\App\Services\LocaleManager::class)->multilingualEnabled() && array_key_exists($lang, config('app.available_locales'))) {
            // Đặt ngôn ngữ trong session
            Session::put('locale', $lang);

            // Hoặc đặt ngôn ngữ trong cookie (tuỳ vào yêu cầu)
            Cookie::queue(Cookie::make('locale', $lang, 60 * 24 * 30)); // Lưu trong 30 ngày
        }

        return redirect()->back();
    }
}
