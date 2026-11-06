<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage;

use Modules\CmsFrontend\Homepage\Contracts\HomepageBlockDefinition;
use Modules\CmsFrontend\Homepage\Contracts\HomepageThemeLayout;

/**
 * Nơi DUY NHẤT khai báo các loại cụm (Definition) và bố cục của từng theme. Thêm một loại cụm/theme mới = thêm class rồi liệt kê ở đây;
 * controller và Blade không giữ danh sách cứng nào. Khác Feature Registry (chức năng hệ thống): đây chỉ là registry của trang chủ.
 */
final class HomepageBlockRegistry
{
    /** @var list<class-string<HomepageBlockDefinition>> */
    private const DEFINITIONS = [
        Definitions\HeroBlock::class,
        Definitions\NoticeTickerBlock::class,
        Definitions\ArticleListBlock::class,
        Definitions\InformationBlock::class,
        Definitions\DocumentsBlock::class,
        Definitions\SchedulesTraceBlock::class,
        Definitions\ActivitiesBlock::class,
        Definitions\MediaBlock::class,
        Definitions\PartnersSupportBlock::class,
        Definitions\StatisticsBlock::class,
        Definitions\ShortcutsBlock::class,
        Definitions\SectionBlock::class,
        Definitions\AudienceLinksBlock::class,
        Definitions\QuickLinksBlock::class,
        Definitions\CallToActionBlock::class,
        Definitions\ContactBlock::class,
    ];

    /** @var list<class-string<HomepageThemeLayout>> */
    private const THEMES = [
        Themes\DefaultHomepageLayout::class,
        Themes\NamBinhDuongHomepageLayout::class,
        Themes\BanDeAnHomepageLayout::class,
    ];

    /** @var array<string, HomepageBlockDefinition>|null */
    private ?array $definitions = null;

    /** @var array<string, HomepageThemeLayout>|null */
    private ?array $layouts = null;

    /** @return array<string, HomepageBlockDefinition> */
    public function definitions(): array
    {
        if ($this->definitions === null) {
            $this->definitions = [];
            foreach (self::DEFINITIONS as $class) {
                $definition = new $class;
                $this->definitions[$definition->key()] = $definition;
            }
        }

        return $this->definitions;
    }

    public function definition(?string $key): ?HomepageBlockDefinition
    {
        return $key === null ? null : ($this->definitions()[$key] ?? null);
    }

    /** @return array<string, HomepageThemeLayout> */
    public function layouts(): array
    {
        if ($this->layouts === null) {
            $this->layouts = [];
            foreach (self::THEMES as $class) {
                $layout = new $class;
                $this->layouts[$layout->themeKey()] = $layout;
            }
        }

        return $this->layouts;
    }

    public function layout(?string $theme): ?HomepageThemeLayout
    {
        return $theme === null ? null : ($this->layouts()[$theme] ?? null);
    }

    /** Mọi instance key mà ít nhất một theme khai báo, kèm definition: instance key => definition key. @return array<string, string> */
    public function instanceKeys(): array
    {
        $keys = [];
        foreach ($this->layouts() as $layout) {
            foreach ($layout->blocks() as $instance => $declaration) {
                $keys[$instance] = (string) $declaration['definition'];
            }
        }

        return $keys;
    }

    /** File Blade của renderer, CHỈ trong thư mục của chính theme (không tính fallback sang theme khác). */
    public function rendererPath(string $theme, string $renderer): ?string
    {
        if (preg_match('/^[a-z0-9-]+(?:\.[a-z0-9-]+)*$/D', $theme.'.'.$renderer) !== 1 || preg_match('/^[a-z0-9-]+$/D', $theme) !== 1) {
            return null;
        }

        return module_path('CmsFrontend', 'resources/views/screens/'.$theme.'/components/home/'.str_replace('.', '/', $renderer).'.blade.php');
    }

    public function rendererExists(string $theme, string $renderer): bool
    {
        $path = $this->rendererPath($theme, $renderer);

        return $path !== null && is_file($path);
    }

    /**
     * Lỗi effect/asset của một variant trong theme (rỗng = dùng được): preset phải nằm trong EffectCatalog, đủ file nguồn
     * và entry của nó có trong build đang phục vụ.
     *
     * @return list<string>
     */
    public function effectProblems(string $theme, string $instance, string $variant): array
    {
        $effects = (array) ($this->layout($theme)?->blocks()[$instance]['variants'][$variant]['effects'] ?? []);

        return EffectCatalog::problems(array_values(array_filter($effects, 'is_string')));
    }

    /**
     * Kiểm tra tính nhất quán của khai báo trong code (không động tới database).
     *
     * @return string[]
     */
    public function selfCheck(): array
    {
        $errors = [];
        foreach ($this->definitions() as $key => $definition) {
            if (preg_match('/^[a-z][a-z0-9-]*$/D', $key) !== 1) {
                $errors[] = 'definition '.$key.': key không hợp lệ';
            }
        }
        foreach ($this->layouts() as $theme => $layout) {
            $zones = $layout->zones();
            foreach ($layout->blocks() as $instance => $declaration) {
                if ($this->definition($declaration['definition'] ?? null) === null) {
                    $errors[] = $theme.'/'.$instance.': thiếu Definition';
                }
                foreach ((array) ($declaration['zones'] ?? []) as $zone) {
                    if (! isset($zones[$zone])) {
                        $errors[] = $theme.'/'.$instance.': zone '.$zone.' không được theme khai báo';
                    }
                }
                if (! isset($declaration['variants'][$declaration['default_variant'] ?? ''])) {
                    $errors[] = $theme.'/'.$instance.': variant mặc định không hợp lệ';
                }
            }
            foreach ($layout->blocks() as $instance => $declaration) {
                foreach (array_keys($declaration['variants']) as $variant) {
                    foreach (EffectCatalog::problems(array_values(array_filter((array) ($declaration['variants'][$variant]['effects'] ?? []), 'is_string'))) as $problem) {
                        if (str_starts_with($problem, 'unknown:')) {
                            $errors[] = $theme.'/'.$instance.'/'.$variant.': effect '.$problem;
                        }
                    }
                }
            }
            foreach ($layout->defaultLayout() as $row) {
                if (! isset($layout->blocks()[$row['instance']])) {
                    $errors[] = $theme.': bố cục mặc định chứa cụm chưa khai báo '.$row['instance'];
                }
            }
        }

        return $errors;
    }
}
