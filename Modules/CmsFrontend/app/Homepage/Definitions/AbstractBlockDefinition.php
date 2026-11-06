<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage\Definitions;

use Modules\CmsFrontend\Homepage\Contracts\HomepageBlockDefinition;

/** Khung chung của Definition: lang key theo quy ước `homepage.blocks.<key>.name|description`, mặc định lấy từ schema. */
abstract class AbstractBlockDefinition implements HomepageBlockDefinition
{
    protected string $key = '';

    protected string $icon = 'ri-layout-grid-line';

    protected ?string $route = null;

    /** @var string[] */

    /** @var array<string, array<string, mixed>> */
    protected array $schema = [];

    /** @var array<string, string> */
    protected array $binding = [];

    public function key(): string
    {
        return $this->key;
    }

    public function nameKey(): string
    {
        return 'homepage.blocks.'.$this->key.'.name';
    }

    public function descriptionKey(): string
    {
        return 'homepage.blocks.'.$this->key.'.description';
    }

    public function icon(): string
    {
        return $this->icon;
    }

    public function settingsSchema(): array
    {
        return $this->schema;
    }

    public function defaults(): array
    {
        $defaults = [];
        foreach ($this->schema as $field => $schema) {
            if (($schema['type'] ?? '') !== 'management_link') {
                $defaults[$field] = $schema['default'] ?? (($schema['type'] ?? '') === 'categories' ? [] : null);
            }
        }

        return $defaults;
    }

    public function managementRoute(): ?string
    {
        return $this->route;
    }

    public function legacyBinding(): array
    {
        return $this->binding;
    }

    public function prefetch(array $allSettings): void {}

    public function resolve(array $settings, array $data): array
    {
        return [];
    }

    /** Giá trị `lvb:<id>` / `nvb:<id>` -> cột và ID cần lọc; trống hoặc sai định dạng = văn bản mới nhất. @return array{column: string, id: int}|null */
    protected static function documentFilter(mixed $value): ?array
    {
        if (preg_match('/^(lvb|nvb):(\d+)$/D', trim((string) $value), $match) !== 1) {
            return null;
        }

        return ['column' => $match[1].'_id', 'id' => (int) $match[2]];
    }

    /** Nội dung chưa có bản dịch (văn bản, lịch, truy xuất) không hiện trên trang ngôn ngữ khác, giống HomePresentation::prepare(). */
    protected static function translatedPage(): bool
    {
        return app(\App\Services\LocaleManager::class)->publicLocale() !== null;
    }

    /**
     * Liên kết tới chức năng quản lý nội dung riêng (field loại management_link).
     *
     * @return array<string, mixed>
     */
    protected static function link(string $route, string $label): array
    {
        return ['type' => 'management_link', 'route' => $route, 'label' => $label];
    }
}
