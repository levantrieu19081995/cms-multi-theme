<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Developer;

use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Modules\CmsBackend\Support\AssetProfile;
use Modules\CmsFrontend\Homepage\EffectCatalog;
use Modules\CmsFrontend\Homepage\HomepageBlockRegistry;
use Modules\CmsFrontend\Homepage\HomepageLayoutService;

/**
 * Nghiệp vụ của tab "Bố cục trang chủ" trong Developer Settings. Chỉ gọi từ route nằm sau middleware cms.developer (phiên Developer);
 * không liên kết với tài khoản CMS. Server validate lại toàn bộ như lúc áp dụng thật, không tin dữ liệu trình duyệt.
 */
final class HomepageLayoutAdmin
{
    /** Kích thước xem trước: khóa lang => độ rộng khung (CSS). */
    public const DEVICES = ['desktop' => '100%', 'tablet' => '820px', 'mobile' => '390px'];

    public function __construct(private HomepageBlockRegistry $registry, private HomepageLayoutService $layouts) {}

    /** Theme có thể cấu hình trong bản triển khai này (bản build một theme chỉ có asset của theme đó). @return array<string, string> key => label */
    public function themes(): array
    {
        $themes = [];
        foreach ($this->registry->layouts() as $key => $layout) {
            if (AssetProfile::known() && ! in_array($key, AssetProfile::selectable(), true)) {
                continue;
            }
            $themes[$key] = (string) config('cmsbackend.screens.themes.'.$key.'.label', $key);
        }

        return $themes;
    }

    public function theme(?string $requested): ?string
    {
        $themes = $this->themes();
        if ($requested !== null && isset($themes[$requested])) {
            return $requested;
        }
        $active = app(\Modules\CmsBackend\Services\ScreenTheme::class)->key('public');

        return isset($themes[$active]) ? $active : (array_key_first($themes));
    }

    /**
     * Dữ liệu dựng tab: theme, chế độ, zone và cụm (Working layout = dòng Live nếu đã cài, ngược lại bố cục mặc định của theme).
     *
     * @return array<string, mixed>
     */
    public function panel(?string $requested): array
    {
        $ready = $this->layouts->ready();
        $theme = $this->theme($requested);
        $base = ['ready' => $ready, 'themes' => $this->themes(), 'theme' => $theme, 'registry_errors' => $this->registry->selfCheck()];
        $layout = $this->registry->layout($theme);
        if ($theme === null || $layout === null) {
            return $base + ['layout' => false];
        }
        $stored = $ready ? $this->layouts->rows($theme) : [];
        $rows = $stored ?: $this->layouts->defaultRows($theme);
        $byInstance = [];
        foreach ($rows as $row) {
            $byInstance[$row['instance']] = $row;
        }
        $zones = [];
        foreach ($layout->zones() as $zone => $labelKey) {
            $zones[$zone] = ['key' => $zone, 'label' => $labelKey, 'blocks' => []];
        }
        foreach ($layout->blocks() as $instance => $declaration) {
            $row = $byInstance[$instance] ?? ['instance' => $instance, 'zone' => $declaration['zones'][0], 'variant' => $declaration['default_variant'], 'enabled' => false, 'sort' => 900];
            $definition = $this->registry->definition($declaration['definition']);
            $variants = [];
            foreach ($declaration['variants'] as $variant => $info) {
                $variants[$variant] = [
                    'renderer' => $info['renderer'], 'effects' => implode(', ', (array) ($info['effects'] ?? [])),
                    'ok' => $this->registry->rendererExists($theme, (string) $info['renderer']) && $this->registry->effectProblems($theme, $instance, $variant) === [],
                ];
            }
            $zone = isset($zones[$row['zone']]) ? $row['zone'] : $declaration['zones'][0];
            $zones[$zone]['blocks'][] = [
                'instance' => $instance, 'definition' => $declaration['definition'], 'definition_ok' => $definition !== null,
                'name' => $definition !== null ? $definition->nameKey() : null, 'description' => $definition?->descriptionKey(), 'icon' => $definition?->icon() ?? 'ri-question-line',
                'allowed_zones' => $declaration['zones'], 'variants' => $variants,
                'variant' => isset($variants[$row['variant']]) ? $row['variant'] : $declaration['default_variant'],
                'effect' => $row['effect'] ?? null,
                'native_effects' => array_values((array) ($declaration['variants'][$declaration['default_variant']]['effects'] ?? [])),
                'enabled' => (bool) $row['enabled'], 'sort' => (int) $row['sort'],
                'can_enable' => $definition !== null && collect($variants)->contains('ok', true),
            ];
        }
        foreach ($zones as &$zone) {
            usort($zone['blocks'], fn ($a, $b) => $a['sort'] <=> $b['sort']);
        }
        unset($zone);

        return $base + [
            'layout' => true, 'zones' => $zones, 'effect_ready' => $this->layouts->effectColumn(), 'effect_choices' => EffectCatalog::CHOICES,
            'installed' => $stored !== [], 'default_only' => $stored === [],
        ];
    }

    /**
     * Validate và tạo preview snapshot (chưa đổi Live).
     *
     * @param  mixed  $payload  JSON do form gửi (danh sách {instance, zone, variant, enabled})
     * @return string token
     */
    public function preview(string $theme, mixed $payload, string $sessionId): string
    {
        $rows = $this->decode($payload);
        $result = $this->layouts->validate($theme, $rows);
        if ($result['errors'] !== []) {
            throw new InvalidArgumentException('preview_invalid:'.$result['errors'][0]['code'].':'.$result['errors'][0]['instance']);
        }

        return $this->layouts->createSnapshot($theme, $result['rows'], $sessionId);
    }

    /**
     * Áp dụng ngay không qua xem trước: validate như lúc áp dụng thật, chặn khi thiếu renderer/effect, ghi trong transaction, ghi nhật ký.
     *
     * @param  mixed  $payload  JSON do form gửi (danh sách {instance, zone, variant, effect, enabled})
     * @return list<array<string, mixed>> các dòng đã ghi
     */
    public function applyNow(string $theme, mixed $payload, ?string $ip): array
    {
        $this->assertTheme($theme);
        $result = $this->layouts->validate($theme, $this->decode($payload));
        if ($result['errors'] !== []) {
            throw new InvalidArgumentException('preview_invalid:'.$result['errors'][0]['code'].':'.$result['errors'][0]['instance']);
        }
        if ($this->blocking($theme, $result['rows']) !== []) {
            throw new InvalidArgumentException('apply_blocked');
        }
        $before = $this->layouts->rows($theme);
        $rows = $this->layouts->apply($theme, $result['rows']);
        DeveloperAudit::record(['action' => 'homepage_layout_apply_now', 'theme' => $theme, 'before' => $before, 'after' => $rows, 'ip' => $ip], 'Developer Settings: áp dụng ngay bố cục trang chủ '.$theme);

        return $rows;
    }

    /**
     * Thông tin cho trang Review.
     *
     * @return array<string, mixed>|null null khi token sai/hết hạn/khác phiên
     */
    public function review(string $token, string $sessionId): ?array
    {
        $snapshot = $this->layouts->snapshot($token, $sessionId);
        if ($snapshot === null) {
            return null;
        }
        $plan = $this->layouts->plan($snapshot['theme'], $snapshot['rows']);
        $activeTheme = app(\Modules\CmsBackend\Services\ScreenTheme::class)->choices('public');

        return [
            'devices' => self::DEVICES, 'token' => $token, 'theme' => $snapshot['theme'], 'theme_label' => (string) config('cmsbackend.screens.themes.'.$snapshot['theme'].'.label', $snapshot['theme']),
            'created_at' => Carbon::parse($snapshot['created_at']), 'issues' => $plan['issues'], 'differs' => $this->layouts->differsFromLive($snapshot['theme'], $snapshot['rows']),
            'renderable' => isset($activeTheme[$snapshot['theme']]), 'blocking' => $this->blocking($snapshot['theme'], $snapshot['rows']),
        ];
    }

    /** Áp dụng snapshot thành Live (validate lại, ghi trong transaction). @return list<array<string, mixed>> */
    public function apply(string $token, string $sessionId, ?string $ip): array
    {
        $snapshot = $this->layouts->snapshot($token, $sessionId);
        if ($snapshot === null) {
            throw new InvalidArgumentException('preview_expired');
        }
        if ($this->blocking($snapshot['theme'], $snapshot['rows']) !== []) {
            throw new InvalidArgumentException('apply_blocked');
        }
        $before = $this->layouts->rows($snapshot['theme']);
        $rows = $this->layouts->apply($snapshot['theme'], $snapshot['rows']);
        DeveloperAudit::record(['action' => 'homepage_layout_apply', 'theme' => $snapshot['theme'], 'before' => $before, 'after' => $rows, 'ip' => $ip], 'Developer Settings: áp dụng bố cục trang chủ '.$snapshot['theme']);
        $this->layouts->forgetSnapshot($token);

        return $rows;
    }

    public function install(string $theme, ?string $ip): int
    {
        $this->assertTheme($theme);
        $created = $this->layouts->installDefaults($theme);
        DeveloperAudit::record(['action' => 'homepage_layout_install', 'theme' => $theme, 'created' => $created, 'ip' => $ip], 'Developer Settings: cài bố cục trang chủ mặc định '.$theme);

        return $created;
    }

    public function reset(string $theme, ?string $ip): void
    {
        $this->assertTheme($theme);
        $this->layouts->resetToDefault($theme);
        DeveloperAudit::record(['action' => 'homepage_layout_reset', 'theme' => $theme, 'ip' => $ip], 'Developer Settings: khôi phục bố cục trang chủ mặc định '.$theme);
    }

    /**
     * Lỗi chặn áp dụng: cụm đang bật nhưng thiếu Definition/renderer/zone/variant.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, string>>
     */
    private function blocking(string $theme, array $rows): array
    {
        return array_merge($this->layouts->validate($theme, $rows)['errors'], $this->layouts->plan($theme, $rows)['issues']);
    }

    private function assertTheme(string $theme): void
    {
        if (! isset($this->themes()[$theme])) {
            throw new InvalidArgumentException('theme_unknown');
        }
    }

    /** @return list<array<string, mixed>> */
    private function decode(mixed $payload): array
    {
        $rows = is_string($payload) ? json_decode($payload, true) : $payload;
        if (! is_array($rows) || count($rows) > 200) {
            throw new InvalidArgumentException('preview_invalid:payload:');
        }

        return array_values(array_filter($rows, 'is_array'));
    }
}
