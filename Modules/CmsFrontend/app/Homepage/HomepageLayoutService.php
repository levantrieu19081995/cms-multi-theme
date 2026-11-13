<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\CmsFrontend\Homepage\Models\HomepageBlockInstance;
use Modules\CmsFrontend\Homepage\Models\HomepageThemeBlock;

/**
 * Bố cục trang chủ theo theme. Ba trạng thái tách bạch:
 *  - Live: các dòng homepage_theme_blocks (đã áp dụng), cache theo theme;
 *  - Working: bản đang chỉnh trong form Developer (chỉ ở trình duyệt, chưa lưu);
 *  - Preview snapshot: bản tạm phía server (cache, gắn Developer session, có thời hạn), KHÔNG đổi Live.
 * Trang chủ công khai luôn dựng theo bố cục này (không còn chế độ Legacy).
 */
final class HomepageLayoutService
{
    public const CACHE_KEY = 'cms.homepage-layout.v2.';

    private const PREVIEW_KEY = 'cms.homepage-preview.';

    private const PREVIEW_MINUTES = 30;

    private static ?bool $ready = null;

    private static ?bool $effectColumn = null;

    public function __construct(private HomepageBlockRegistry $registry, private HomepageSettingsService $settings) {}

    /** Cột `effect_key` đã có chưa (migration thêm cột chưa chạy thì lựa chọn hiệu ứng chưa lưu được và cụm dùng hiệu ứng mặc định của theme). */
    public function effectColumn(): bool
    {
        return $this->ready() && (self::$effectColumn ??= Schema::hasColumn('homepage_theme_blocks', 'effect_key'));
    }

    /**
     * Chuẩn hóa lựa chọn hiệu ứng: rỗng/`default` -> null (hiệu ứng mặc định của theme). Giá trị ngoài allow-list: null, hoặc `false` khi `$strict`.
     */
    private function choice(mixed $value, bool $strict = false): string|false|null
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '' || $value === 'default') {
            return null;
        }

        return in_array($value, EffectCatalog::CHOICES, true) ? $value : ($strict ? false : null);
    }

    /**
     * Hiệu ứng thực tế của cụm: chưa chọn = hiệu ứng theme khai báo cho variant (hiện trạng của partial); `none` = chỉ giữ hiệu ứng cấu trúc; còn lại = preset đã chọn (cộng hiệu ứng cấu trúc).
     *
     * @param  array<string, mixed>  $declaration
     * @return list<string>
     */
    private function effectiveEffects(array $declaration, string $variant, ?string $choice): array
    {
        $native = array_values(array_filter((array) ($declaration['variants'][$variant]['effects'] ?? []), 'is_string'));
        if ($choice === null) {
            return $native;
        }
        // Hiệu ứng cấu trúc (slider banner, vòng ảnh) nằm trong HTML riêng của cụm nên được giữ; chỉ reveal/stagger/count-up thay đổi theo lựa chọn.
        $structural = array_values(array_intersect($native, EffectCatalog::STRUCTURAL));

        return $choice === 'none' ? $structural : array_values(array_unique([...$structural, $choice]));
    }

    /** Chỉ dùng trong test: đặt lại kết quả `ready()` (null = kiểm tra lại database, true/false = giả lập). */
    public static function fakeReady(?bool $ready): void
    {
        self::$ready = $ready;
        self::$effectColumn = $ready === null ? null : $ready;
    }

    /** Ba bảng của chức năng đã được migrate chưa (chưa migrate thì trang chủ dùng bố cục mặc định trong code và không đụng tới chúng). */
    public function ready(): bool
    {
        return self::$ready ??= Schema::hasTable('homepage_block_instances') && Schema::hasTable('homepage_theme_blocks') && Schema::hasTable('homepage_block_settings');
    }

    /**
     * Các dòng Live của theme, dạng [instance, zone, variant, enabled, sort].
     *
     * @return list<array{instance: string, zone: string, variant: string, enabled: bool, sort: int}>
     */
    public function rows(string $theme): array
    {
        if (! $this->ready()) {
            return [];
        }

        return $this->withSplitBlocks($theme, HomepageThemeBlock::query()->with('instance:id,instance_key')->where('theme_key', $theme)
            ->orderBy('sort_order')->orderBy('id')->get()
            ->filter(fn ($row) => $row->instance !== null)
            ->map(fn ($row) => [
                'instance' => $row->instance->instance_key, 'zone' => $row->zone_key, 'variant' => (string) $row->variant_key,
                'effect' => $row->effect_key !== null && $row->effect_key !== '' ? (string) $row->effect_key : null,
                'enabled' => (bool) $row->enabled, 'sort' => (int) $row->sort_order,
            ])->values()->all());
    }

    /**
     * Cụm tách từ một cụm cũ (`split_from`) mà bố cục đã cài chưa có dòng: thêm dòng ngay sau dòng của cụm cũ (cùng zone, bật/tắt và hiệu ứng),
     * để trang chủ đang chạy không đổi sau khi deploy. Dòng của cụm cũ mà theme không còn khai báo thì được thay bằng các cụm tách từ nó.
     * Chỉ đọc; lần lưu bố cục tiếp theo ghi các dòng này vào database.
     *
     * @param  list<array<string, mixed>>  $rows  các dòng Live theo thứ tự sort
     * @return list<array<string, mixed>>
     */
    private function withSplitBlocks(string $theme, array $rows): array
    {
        $blocks = $this->registry->layout($theme)?->blocks() ?? [];
        $present = array_flip(array_column($rows, 'instance'));
        $children = [];
        $replaced = [];
        foreach ($blocks as $instance => $declaration) {
            $parent = $declaration['split_from'] ?? null;
            if (! is_string($parent) || ! isset($present[$parent])) {
                continue;
            }
            if (! isset($blocks[$parent])) {
                $replaced[$parent] = true;
            }
            if (! isset($present[$instance])) {
                $children[$parent][] = $instance;
            }
        }
        if ($children === [] && $replaced === []) {
            return $rows;
        }
        $result = [];
        foreach ($rows as $row) {
            $declared = isset($blocks[$row['instance']]);
            if (! isset($replaced[$row['instance']])) {
                $result[] = $row;
            }
            foreach ($children[$row['instance']] ?? [] as $offset => $child) {
                $declaration = $blocks[$child];
                $result[] = [
                    'instance' => $child, 'zone' => in_array($row['zone'], $declaration['zones'], true) ? $row['zone'] : $declaration['zones'][0],
                    'variant' => $declaration['default_variant'], 'effect' => $row['effect'], 'enabled' => $row['enabled'],
                    'sort' => $row['sort'] + $offset + ($declared ? 1 : 0),
                ];
            }
        }

        return $result;
    }

    /**
     * Kế hoạch render của theme từ các dòng cho trước (Live hoặc snapshot). Cụm lỗi (thiếu Definition/renderer/zone/variant) bị bỏ khỏi zone
     * và ghi vào `issues`; trang không bao giờ lỗi vì một cụm.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{theme: string, zones: array<string, list<array<string, mixed>>>, issues: list<array<string, string>>, stamp: string}
     */
    public function plan(string $theme, array $rows): array
    {
        $layout = $this->registry->layout($theme);
        $zones = $layout === null ? [] : array_fill_keys(array_keys($layout->zones()), []);
        $issues = [];
        $zoneOrder = array_flip(array_keys($zones));
        usort($rows, fn ($a, $b) => [$zoneOrder[$a['zone']] ?? 99, (int) $a['sort']] <=> [$zoneOrder[$b['zone']] ?? 99, (int) $b['sort']]);
        foreach ($rows as $row) {
            if (! $row['enabled'] || $layout === null) {
                continue;
            }
            $instance = (string) $row['instance'];
            $declaration = $layout->blocks()[$instance] ?? null;
            $definition = $declaration === null ? null : $this->registry->definition($declaration['definition']);
            if ($declaration === null || $definition === null) {
                $issues[] = ['instance' => $instance, 'code' => 'missing_definition'];

                continue;
            }
            $zone = (string) $row['zone'];
            $variant = (string) ($row['variant'] !== '' ? $row['variant'] : $declaration['default_variant']);
            if (! isset($zones[$zone]) || ! in_array($zone, $declaration['zones'], true)) {
                $issues[] = ['instance' => $instance, 'code' => 'invalid_zone'];

                continue;
            }
            if (! isset($declaration['variants'][$variant])) {
                $issues[] = ['instance' => $instance, 'code' => 'invalid_variant'];

                continue;
            }
            $renderer = (string) $declaration['variants'][$variant]['renderer'];
            if (! $this->registry->rendererExists($theme, $renderer)) {
                $issues[] = ['instance' => $instance, 'code' => 'missing_renderer'];

                continue;
            }
            $choice = $this->choice($row['effect'] ?? null);
            $effects = $this->effectiveEffects($declaration, $variant, $choice);
            if (EffectCatalog::problems($effects) !== []) {
                $issues[] = ['instance' => $instance, 'code' => 'missing_effect'];

                continue;
            }
            $zones[$zone][] = [
                'instance' => $instance, 'definition' => $definition->key(), 'zone' => $zone, 'variant' => $variant,
                'renderer' => $renderer, 'sort' => (int) $row['sort'],
                'effect' => $choice, 'effects' => $effects,
                'settings' => $this->settings->read($theme, $instance),
            ];
        }

        return ['theme' => $theme, 'zones' => $zones, 'issues' => $issues, 'stamp' => hash('sha256', json_encode([$zones, $issues], JSON_THROW_ON_ERROR))];
    }

    /**
     * Kế hoạch bố cục của theme cho trang chủ công khai, cache theo theme: các dòng đã áp dụng trong database; chưa cài thì bố cục mặc định
     * khai báo trong Theme Layout (nên trang chủ chạy được cả khi chưa migrate). Null chỉ khi theme không có Theme Layout.
     */
    public function live(string $theme): ?array
    {
        if ($this->registry->layout($theme) === null) {
            return null;
        }

        return Cache::rememberForever($this->cacheKey($theme), fn (): array => $this->plan($theme, $this->rows($theme) ?: $this->defaultRows($theme)));
    }

    /**
     * Khóa cache kế hoạch của theme, gắn với khai báo bố cục trong code (cụm, renderer, bố cục mặc định): deploy đổi Theme Layout thì kế hoạch
     * cũ (có thể trỏ tới renderer đã xóa) không còn được đọc, không cần xóa cache bằng tay.
     */
    private function cacheKey(string $theme): string
    {
        $layout = $this->registry->layout($theme);
        $declaration = $layout === null ? '' : json_encode([$layout->blocks(), $layout->defaultLayout()]);

        return self::CACHE_KEY.$theme.'.'.substr(hash('sha256', (string) $declaration), 0, 12);
    }

    /**
     * Validate toàn bộ bố cục như lúc áp dụng thật. Server không tin trình duyệt: zone/variant/renderer đều kiểm tra lại với khai báo trong code.
     *
     * @param  array<int, mixed>  $input
     * @return array{rows: list<array{instance: string, zone: string, variant: string, enabled: bool, sort: int}>, errors: list<array<string, string>>}
     */
    public function validate(string $theme, array $input): array
    {
        $layout = $this->registry->layout($theme);
        if ($layout === null) {
            return ['rows' => [], 'errors' => [['instance' => '', 'code' => 'theme_unknown']]];
        }
        $declared = $layout->blocks();
        $zones = $layout->zones();
        $errors = [];
        $out = [];
        $counter = array_fill_keys(array_keys($zones), 0);
        $stored = [];
        foreach ($this->rows($theme) ?: $this->defaultRows($theme) as $storedRow) {
            $stored[$storedRow['instance']] = $storedRow;
        }
        foreach ($input as $row) {
            if (! is_array($row)) {
                continue;
            }
            $instance = (string) ($row['instance'] ?? '');
            $declaration = $declared[$instance] ?? null;
            if ($declaration === null) {
                $errors[] = ['instance' => $instance, 'code' => 'unknown_instance'];

                continue;
            }
            if (isset($out[$instance])) {
                $errors[] = ['instance' => $instance, 'code' => 'duplicate'];

                continue;
            }
            $zone = (string) ($row['zone'] ?? '');
            $variant = (string) (($row['variant'] ?? '') !== '' ? $row['variant'] : $declaration['default_variant']);
            $enabled = filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (! isset($zones[$zone]) || ! in_array($zone, $declaration['zones'], true)) {
                $errors[] = ['instance' => $instance, 'code' => 'invalid_zone'];

                continue;
            }
            if (! isset($declaration['variants'][$variant])) {
                $errors[] = ['instance' => $instance, 'code' => 'invalid_variant'];

                continue;
            }
            // Hiệu ứng: không gửi = giữ giá trị đang lưu; rỗng/`default` = mặc định của theme; còn lại phải nằm trong allow-list.
            $effect = array_key_exists('effect', $row) ? $this->choice($row['effect'], true) : ($stored[$instance]['effect'] ?? null);
            if ($effect === false) {
                $errors[] = ['instance' => $instance, 'code' => 'invalid_effect'];

                continue;
            }
            if ($enabled) {
                if ($this->registry->definition($declaration['definition']) === null) {
                    $errors[] = ['instance' => $instance, 'code' => 'missing_definition'];
                } elseif (! $this->registry->rendererExists($theme, (string) $declaration['variants'][$variant]['renderer'])) {
                    $errors[] = ['instance' => $instance, 'code' => 'missing_renderer'];
                } elseif (EffectCatalog::problems($this->effectiveEffects($declaration, $variant, $effect)) !== []) {
                    $errors[] = ['instance' => $instance, 'code' => 'missing_effect'];
                }
            }
            $counter[$zone] += 10;
            $out[$instance] = ['instance' => $instance, 'zone' => $zone, 'variant' => $variant, 'effect' => $effect, 'enabled' => $enabled, 'sort' => $counter[$zone]];
        }
        // Cụm không có trong dữ liệu gửi lên giữ nguyên giá trị đang lưu (hoặc bố cục mặc định), không bị xóa.
        foreach ($declared as $instance => $declaration) {
            if (! isset($out[$instance])) {
                $row = $stored[$instance] ?? ['instance' => $instance, 'zone' => $declaration['zones'][0], 'variant' => $declaration['default_variant'], 'effect' => null, 'enabled' => false, 'sort' => 900];
                $out[$instance] = $row;
            }
        }

        return ['rows' => array_values($out), 'errors' => $errors];
    }

    /**
     * Áp dụng bố cục Live: validate lại rồi ghi trong MỘT transaction. Cụm tắt giữ zone/variant/settings để bật lại đúng chỗ.
     *
     * @param  array<int, mixed>  $input
     * @return list<array<string, mixed>> các dòng đã ghi
     *
     * @throws InvalidArgumentException khi còn lỗi (message = mã lỗi đầu tiên)
     */
    public function apply(string $theme, array $input): array
    {
        if (! $this->ready()) {
            throw new InvalidArgumentException('not_ready');
        }
        $result = $this->validate($theme, $input);
        if ($result['errors'] !== []) {
            throw new InvalidArgumentException($result['errors'][0]['code']);
        }
        $this->writeRows($theme, $result['rows']);

        return $result['rows'];
    }

    /** Cài bố cục mặc định (trang chủ hiện tại) cho theme; chạy lại an toàn: chỉ thêm dòng còn thiếu, không đè dòng đã có. */
    public function installDefaults(?string $theme = null): int
    {
        if (! $this->ready()) {
            throw new InvalidArgumentException('not_ready');
        }
        $created = 0;
        foreach ($theme === null ? array_keys($this->registry->layouts()) : [$theme] as $key) {
            $existing = array_column($this->rows($key), 'instance');
            $missing = array_values(array_filter($this->defaultRows($key), fn ($row) => ! in_array($row['instance'], $existing, true)));
            if ($missing !== []) {
                $this->writeRows($key, $missing);
                $created += count($missing);
            }
            // Settings theo theme khởi tạo MỘT LẦN từ option cũ; chạy lại không đè dòng đã có.
            $this->settings->seedFromLegacy($key);
        }

        return $created;
    }

    /** Khôi phục bố cục mặc định của theme (ghi đè vị trí/variant/bật tắt; cấu hình nội dung giữ nguyên). */
    public function resetToDefault(string $theme): void
    {
        if (! $this->ready() || $this->registry->layout($theme) === null) {
            throw new InvalidArgumentException('not_ready');
        }
        $this->writeRows($theme, $this->defaultRows($theme));
    }

    /**
     * Bố cục mặc định của theme dạng dòng đầy đủ: cụm có trong defaultLayout() theo thứ tự đó, cụm theme hỗ trợ nhưng không nằm trong
     * trang chủ hiện tại thì tắt (zone đầu tiên cho phép).
     *
     * @return list<array{instance: string, zone: string, variant: string, enabled: bool, sort: int}>
     */
    public function defaultRows(string $theme): array
    {
        $layout = $this->registry->layout($theme);
        if ($layout === null) {
            return [];
        }
        $rows = [];
        $counter = [];
        foreach ($layout->defaultLayout() as $item) {
            $counter[$item['zone']] = ($counter[$item['zone']] ?? 0) + 10;
            $rows[$item['instance']] = ['instance' => $item['instance'], 'zone' => $item['zone'], 'variant' => $item['variant'], 'effect' => null, 'enabled' => (bool) $item['enabled'], 'sort' => $counter[$item['zone']]];
        }
        foreach ($layout->blocks() as $instance => $declaration) {
            if (! isset($rows[$instance])) {
                $rows[$instance] = ['instance' => $instance, 'zone' => $declaration['zones'][0], 'variant' => $declaration['default_variant'], 'effect' => null, 'enabled' => false, 'sort' => 900];
            }
        }

        return array_values($rows);
    }

    /** Tạo instance hệ thống cho mọi cụm mà các theme khai báo (không đè tên đã có). */
    public function ensureInstances(): void
    {
        foreach ($this->registry->instanceKeys() as $instance => $definitionKey) {
            $definition = $this->registry->definition($definitionKey);
            if ($definition === null) {
                continue;
            }
            HomepageBlockInstance::query()->firstOrCreate(['instance_key' => $instance], [
                'definition_key' => $definitionKey,
                'name_vi' => (string) __($definition->nameKey(), [], 'vi'),
                'name_en' => (string) __($definition->nameKey(), [], 'en'),
                'is_system' => true,
            ]);
        }
    }

    /** @param list<array<string, mixed>> $rows */
    private function writeRows(string $theme, array $rows): void
    {
        DB::transaction(function () use ($theme, $rows): void {
            $this->ensureInstances();
            $ids = HomepageBlockInstance::query()->pluck('id', 'instance_key');
            foreach ($rows as $row) {
                $id = $ids[$row['instance']] ?? null;
                if ($id === null) {
                    throw new InvalidArgumentException('unknown_instance');
                }
                HomepageThemeBlock::query()->updateOrCreate(
                    ['theme_key' => $theme, 'block_instance_id' => $id],
                    ['zone_key' => $row['zone'], 'variant_key' => $row['variant'], 'enabled' => (bool) $row['enabled'], 'sort_order' => (int) $row['sort']]
                        + ($this->effectColumn() ? ['effect_key' => $this->choice($row['effect'] ?? null)] : []),
                );
            }
        });
        $this->flush($theme);
    }

    /** Xóa cache layout đã resolve của theme (gọi khi đổi bố cục, chế độ hoặc cấu hình cụm). */
    public function flush(?string $theme = null): void
    {
        foreach ($theme === null ? array_keys($this->registry->layouts()) : [$theme] as $key) {
            Cache::forget($this->cacheKey($key));
        }
    }

    /**
     * Tạo preview snapshot phía server, gắn với Developer session. Trả về token ngẫu nhiên; dữ liệu KHÔNG nằm trong URL.
     *
     * @param  list<array<string, mixed>>  $rows  các dòng đã validate
     */
    public function createSnapshot(string $theme, array $rows, string $sessionId): string
    {
        $token = Str::random(40);
        Cache::put(self::PREVIEW_KEY.$token, [
            'theme' => $theme, 'rows' => $rows, 'created_at' => now()->toIso8601String(), 'owner' => hash('sha256', $sessionId),
        ], now()->addMinutes(self::PREVIEW_MINUTES));

        return $token;
    }

    /** @return array{theme: string, rows: list<array<string, mixed>>, created_at: string, owner: string}|null null nếu hết hạn, sai token hoặc khác phiên Developer */
    public function snapshot(string $token, string $sessionId): ?array
    {
        if (preg_match('/^[A-Za-z0-9]{40}$/D', $token) !== 1) {
            return null;
        }
        $snapshot = Cache::get(self::PREVIEW_KEY.$token);

        return is_array($snapshot) && hash_equals((string) $snapshot['owner'], hash('sha256', $sessionId)) ? $snapshot : null;
    }

    public function forgetSnapshot(string $token): void
    {
        if (preg_match('/^[A-Za-z0-9]{40}$/D', $token) === 1) {
            Cache::forget(self::PREVIEW_KEY.$token);
        }
    }

    /** Bản snapshot có khác Live không (chưa áp dụng). */
    public function differsFromLive(string $theme, array $rows): bool
    {
        $normalize = function (array $rows): array {
            $out = [];
            foreach ($rows as $row) {
                $out[$row['instance']] = [$row['zone'], $row['variant'], (bool) $row['enabled'], (int) $row['sort'], $this->choice($row['effect'] ?? null)];
            }
            ksort($out);

            return $out;
        };

        return $normalize($rows) !== $normalize($this->rows($theme) ?: $this->defaultRows($theme));
    }
}
