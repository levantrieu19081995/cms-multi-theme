<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage;

use Throwable;

/**
 * Render trang chủ theo zone từ kế hoạch bố cục (Live hoặc preview snapshot). Dùng chung cho cả ba theme: mỗi cụm vẫn được vẽ bằng chính partial
 * của theme (renderer khai báo trong Theme Layout) với bộ dữ liệu trang chủ như cũ, nên HTML, class, ID và `data-*` không đổi.
 */
final class HomepageRenderer
{
    private const REQUEST_KEY = '_homepage_active_plan';

    public function __construct(private HomepageLayoutService $layouts, private HomepageDataResolver $resolver, private HomepageBlockRegistry $registry) {}

    /**
     * Kế hoạch đang áp dụng cho request này (theme không có Theme Layout thì dùng bố cục của theme mặc định). Preview chỉ có hiệu lực khi route frame của Developer đã đặt
     * thuộc tính `_homepage_preview` VÀ snapshot đúng phiên; không bao giờ đọc token từ query string.
     *
     * @return array{plan: array<string, mixed>, preview: bool}|null
     */
    public function active(string $theme): ?array
    {
        $attributes = request()->attributes;
        $cached = $attributes->get(self::REQUEST_KEY.'.'.$theme, false);
        if ($cached !== false) {
            return $cached;
        }
        $result = null;
        $preview = $attributes->get('_homepage_preview');
        if (is_array($preview)) {
            $snapshot = $this->layouts->snapshot((string) ($preview['token'] ?? ''), request()->session()->getId());
            if ($snapshot !== null && $snapshot['theme'] === $theme) {
                $result = ['plan' => $this->layouts->plan($theme, $snapshot['rows']), 'preview' => true];
            }
        } else {
            $plan = $this->layouts->live($theme) ?? $this->layouts->live('default');
            $result = $plan === null ? null : ['plan' => $plan, 'preview' => false];
        }
        $attributes->set(self::REQUEST_KEY.'.'.$theme, $result);

        return $result;
    }

    /**
     * HTML của từng zone. Cụm lỗi không làm hỏng trang: bị bỏ qua (và ghi log); riêng preview hiện khung cảnh báo để Developer thấy.
     *
     * @param  array<string, mixed>  $plan
     * @param  array<string, mixed>  $data
     * @return array<string, string> zone => HTML
     */
    public function zones(array $plan, array $data, bool $preview): array
    {
        $zones = [];
        foreach ($plan['zones'] as $zone => $blocks) {
            $html = '';
            foreach ($blocks as $block) {
                if ($preview) {
                    $html .= $this->missingSettingsNotice($block);
                }
                try {
                    $html .= BlockEffectApplier::apply(view('cmsfrontend::screens.components.home.'.$block['renderer'], array_merge($data, $this->resolver->forBlock($block)))->render(), $block['effect'] ?? null);
                } catch (Throwable $e) {
                    report($e);
                    if ($preview) {
                        $html .= '<div role="alert" style="margin:1rem;padding:1rem;border:2px dashed #dc3545;background:#fff5f5;color:#842029;font:14px/1.5 sans-serif">'
                            .e(__('homepage.render.block_error')).' <code>'.e($block['instance']).'</code></div>';
                    }
                }
            }
            $zones[$zone] = $html;
        }

        return $zones;
    }

    /**
     * Preview only: a block whose essential source (a field the Definition marks `needed`) is not chosen yet shows a clear notice above
     * it instead of looking silently empty. Public pages never show this.
     *
     * @param  array<string, mixed>  $block
     */
    private function missingSettingsNotice(array $block): string
    {
        $schema = $this->registry->definition($block['definition'])?->settingsSchema() ?? [];
        $missing = [];
        foreach ($schema as $field => $definition) {
            if (! empty($definition['needed']) && in_array($block['settings'][$field] ?? null, [null, '', []], true)) {
                $missing[] = (string) __($definition['label'] ?? $field);
            }
        }
        if ($missing === []) {
            return '';
        }

        return '<div role="note" style="margin:1rem;padding:.75rem 1rem;border:2px dashed #ffc107;background:#fff8e1;color:#664d03;font:14px/1.5 sans-serif">'
            .e(__('homepage.render.missing_settings')).' <strong>'.e($block['instance']).'</strong>: '.e(implode(', ', $missing)).'</div>';
    }
}
