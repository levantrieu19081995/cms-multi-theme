<?php

namespace Modules\CmsBackend\Support;

use App\Models\Option;
use Illuminate\Http\Request;

/**
 * Site-wide settings that Khuyến nông shows on the shared "Cài đặt chung" screen (setting.general) instead of
 * "Cài đặt trang chủ": contact details, social links and map. (Theme and the homepage information category are
 * edited on "Cài đặt trang chủ", see HomeSettings.)
 * Stored in the CMS database `options` table. The core settings controller is untouched: the CMS view adds
 * these fields and the cms.settings.common middleware saves them before the core update runs.
 */
final class CommonSettings
{
    public const CONTACT = [
        'web_address' => 'Địa chỉ liên hệ',
        'web_phone' => 'Số điện thoại liên hệ',
        'web_email' => 'Email liên hệ',
    ];

    public const SOCIAL = [
        'web_facebook' => ['Facebook', '/^https?:\/\/(www\.)?facebook\.com\/.+$/i'],
        'web_twitter' => ['Twitter', '/^https?:\/\/(www\.)?twitter\.com\/.+$/i'],
        'web_google_plus' => ['Google Plus', '/^https?:\/\/(plus\.google\.com|www\.google\.com\/\+).+$/i'],
        'web_instagram' => ['Instagram', '/^https?:\/\/(www\.)?instagram\.com\/.+$/i'],
        'web_zalo' => ['Zalo', '/^https?:\/\/((chat|oa)\.)?zalo\.me\/.+/i'],
        'web_tiktok' => ['TikTok', '/^https?:\/\/((www|vm)\.)?tiktok\.com\/.+/i'],
        'web_youtube' => ['Youtube', '/^https?:\/\/(www\.)?(youtube\.com|youtu\.be)\/.+/i'],
        'web_threads' => ['Threads', '/^https?:\/\/(www\.)?threads\.net\/.+/i'],
        'web_linkedin' => ['LinkedIn', '/^https?:\/\/(www\.)?linkedin\.com\/.+/i'],
    ];

    public const MAP = 'web_location';

    /**
     * Chân trang (Cài đặt chung > Chân trang): key => độ dài tối đa. Các theme tự quyết định hiển thị trường nào;
     * trống = footer dùng chữ mặc định của theme. Trường có trong TRANSLATABLE nhập thêm theo từng ngôn ngữ.
     */
    public const FOOTER = [
        'web_email_admin' => 255,
        'footer_unit_name' => 255,
        'footer_unit_short' => 255,
        'footer_unit_name_en' => 255,
        'footer_parent_unit' => 255,
        'footer_description' => 500,
        'footer_slogan' => 255,
        'footer_copyright' => 255,
        'footer_publisher' => 500,
        'footer_source_note' => 500,
        'footer_design_credit' => 255,
        'footer_image_credit' => 500,
    ];

    /** Texts shown on the public pages that exist per language (option_translations); the default language stays in `options`. */
    public const TRANSLATABLE = [
        'web_title', 'web_description', 'web_address',
        'footer_unit_name', 'footer_description', 'footer_slogan', 'footer_copyright',
        'footer_publisher', 'footer_source_note', 'footer_design_credit', 'footer_image_credit',
    ];

    /** @return string[] */
    public static function keys(): array
    {
        return [...array_keys(self::CONTACT), ...array_keys(self::SOCIAL), self::MAP, ...array_keys(self::FOOTER)];
    }

    /** @return array<string, string> key => stored value ('' when not set) */
    public static function values(): array
    {
        $stored = Option::whereIn('key', self::keys())->pluck('value', 'key');

        return collect(self::keys())->mapWithKeys(fn ($key) => [$key => (string) ($stored[$key] ?? '')])->all();
    }

    public static function rules(): array
    {
        $rules = [
            'web_address' => ['nullable', 'string', 'max:500'],
            'web_phone' => ['nullable', 'string', 'max:255'],
            'web_email' => ['nullable', 'string', 'max:255'],
            self::MAP => ['nullable', 'string', 'max:2000'],
        ];
        foreach (self::SOCIAL as $key => [, $pattern]) {
            $rules[$key] = ['nullable', 'url', 'regex:'.$pattern];
        }
        foreach (self::FOOTER as $key => $max) {
            $rules[$key] = ['nullable', 'string', 'max:'.$max];
        }

        return $rules;
    }

    /** Validates and stores the fields; only keys present in the form are written. */
    public static function save(Request $request): void
    {
        $request->validate(self::rules(), [
            'regex' => __('admin.message.duong_dan_khong_dung_dinh_dang_cua_mang_xa_hoi_n'),
            'url' => __('admin.message.duong_dan_phai_la_dia_chi_web_hop_le_http_https'),
        ]);
        foreach (self::keys() as $key) {
            if (! $request->has($key)) {
                continue;
            }
            Option::updateOrCreate(['key' => $key], ['value' => trim((string) $request->input($key, '')), 'autoload' => 1]);
        }
        self::saveTranslations($request);
    }

    /** Other-language texts posted as option_translations[key][locale]; only keys present in the form are written. */
    public static function saveTranslations(Request $request): void
    {
        $locales = app(\App\Services\LocaleManager::class);
        $rules = [];
        foreach (self::TRANSLATABLE as $key) {
            foreach ($locales->alternates() as $locale) {
                $rules["option_translations.$key.$locale"] = ['nullable', 'string', 'max:500'];
            }
        }
        $request->validate($rules);
        foreach (self::TRANSLATABLE as $key) {
            $posted = $request->input("option_translations.$key");
            if (! is_array($posted)) {
                continue;
            }
            app(\App\Services\TranslationService::class)->saveOptionTranslations($key, array_intersect_key($posted, array_flip($locales->alternates())));
        }
    }
}
