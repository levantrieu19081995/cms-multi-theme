<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage;

/**
 * Danh sách effect preset được phép dùng cho cụm trang chủ (allow-list nằm trong code, khớp `effects/EFFECTS.md`). Effect thuộc KHUNG dùng chung:
 * mã nằm ở `resources/assets/effects/`, không chứa tên theme, và mọi build (kể cả build một theme) đều đóng gói đủ entry của chúng
 * (`sharedEffectEntries` trong scripts/theme-profiles.mjs). Theme Layout chỉ khai báo KEY preset; database không lưu JS/CSS/selector.
 *
 * Trang chủ chỉ nạp entry của các effect mà cụm đang bật khai báo (`entriesForPlan()` -> `@vite` trong home.blade.php của theme). Mỗi entry
 * `effects/auto/*.js` tự khởi chạy theo hook HTML khi được nạp.
 */
final class EffectCatalog
{
    /** Lựa chọn hiệu ứng của một cụm (ngoài "mặc định của theme" = chưa chọn): `none` gỡ hiệu ứng, `reveal`/`stagger` gắn hiệu ứng dùng chung. */
    public const CHOICES = ['none', 'reveal', 'stagger'];

    /** Hiệu ứng gắn cấu trúc HTML riêng của cụm (slider banner, vòng ảnh): không gỡ/gắn được bằng lựa chọn, luôn giữ khi cụm có sẵn. */
    public const STRUCTURAL = ['crossfade-slider', 'gallery-orbit'];

    private const ROOT = 'Modules/CmsFrontend/resources/assets/effects/';

    /**
     * Preset => file nguồn và entry nạp trang (`reveal` khởi tạo luôn stagger và count-up, xem EFFECTS.md).
     *
     * @var array<string, array{files: string[], entry: string}>
     */
    private const PRESETS = [
        'reveal' => ['files' => ['reveal/index.js', 'reveal/style.css'], 'entry' => 'reveal'],
        'stagger' => ['files' => ['stagger/index.js', 'stagger/style.css'], 'entry' => 'reveal'],
        'count-up' => ['files' => ['count-up/index.js'], 'entry' => 'reveal'],
        'crossfade-slider' => ['files' => ['crossfade-slider/index.js', 'crossfade-slider/style.css'], 'entry' => 'crossfade-slider'],
        'gallery-orbit' => ['files' => ['gallery-orbit/index.js', 'gallery-orbit/style.css'], 'entry' => 'gallery-orbit'],
    ];

    /**
     * Entry => các file entry (Vite) của nó, đường dẫn tính từ thư mục effects/.
     *
     * @var array<string, string[]>
     */
    private const ENTRIES = [
        'reveal' => ['auto/reveal.js'],
        'crossfade-slider' => ['auto/crossfade-slider.js', 'crossfade-slider/style.css'],
        'gallery-orbit' => ['auto/gallery-orbit.js', 'gallery-orbit/style.css'],
    ];

    /** @var array<string, mixed>|null|false false = đọc manifest thật; chỉ test đặt giá trị khác. */
    private static array|null|false $fake = false;

    /** Chỉ dùng trong test: giả lập manifest của build (null = không kiểm được, false = về manifest thật). */
    public static function fakeManifest(array|null|false $manifest): void
    {
        self::$fake = $manifest;
    }

    /**
     * Entry cần nạp cho các cụm của kế hoạch render (khóa như `reveal`, `crossfade-slider`, `gallery-orbit`).
     *
     * @param  array{zones: array<string, list<array<string, mixed>>>}  $plan
     * @return list<string>
     */
    public static function entriesForPlan(array $plan): array
    {
        $entries = [];
        foreach ($plan['zones'] as $blocks) {
            foreach ($blocks as $block) {
                foreach ((array) ($block['effects'] ?? []) as $preset) {
                    if (isset(self::PRESETS[$preset])) {
                        $entries[self::PRESETS[$preset]['entry']] = true;
                    }
                }
            }
        }

        return array_keys($entries);
    }

    /**
     * Lỗi của danh sách preset một variant khai báo (rỗng = dùng được).
     *
     * @param  string[]  $presets
     * @return list<string> mã lỗi: `unknown:<key>`, `file:<path>`, `build:<path>`
     */
    public static function problems(array $presets): array
    {
        $problems = [];
        $manifest = self::manifest();
        foreach ($presets as $key) {
            $preset = self::PRESETS[$key] ?? null;
            if ($preset === null) {
                $problems[] = 'unknown:'.$key;

                continue;
            }
            foreach ($preset['files'] as $file) {
                if (! is_file(base_path(self::ROOT.$file))) {
                    $problems[] = 'file:'.self::ROOT.$file;
                }
            }
            foreach (self::ENTRIES[$preset['entry']] as $entry) {
                if (! is_file(base_path(self::ROOT.$entry))) {
                    $problems[] = 'file:'.self::ROOT.$entry;
                } elseif ($manifest !== null && ! array_key_exists(self::ROOT.$entry, $manifest)) {
                    $problems[] = 'build:'.self::ROOT.$entry;
                }
            }
        }

        return array_values(array_unique($problems));
    }

    /** Manifest của build đang phục vụ, hoặc null khi chạy Vite dev / chưa build (không kiểm được). @return array<string, mixed>|null */
    private static function manifest(): ?array
    {
        if (self::$fake !== false) {
            return self::$fake;
        }
        if (is_file(public_path('hot'))) {
            return null;
        }
        $file = public_path('build/manifest.json');
        if (! is_file($file)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($file), true);

        return is_array($data) ? $data : null;
    }
}
