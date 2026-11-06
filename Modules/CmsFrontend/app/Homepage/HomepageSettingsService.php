<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Homepage;

use App\Models\Option;
use App\Models\Taxonomy;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\CmsBackend\Support\HomeSettings;
use Modules\CmsFrontend\Homepage\Contracts\HomepageBlockDefinition;
use Modules\CmsFrontend\Homepage\Models\HomepageBlockInstance;
use Modules\CmsFrontend\Homepage\Models\HomepageBlockSetting;

/**
 * Cấu hình NGUỒN NỘI DUNG của từng cụm theo theme, validate theo `settingsSchema()` của Definition (server là nơi quyết định).
 *
 * Nguồn sự thật theo theme: homepage_block_settings, khóa (theme_key, block_instance_id). Option cũ (toàn cục, không có theme) CHỈ dùng để
 * khởi tạo một lần (`seedFromLegacy()` khi cài bố cục, hoặc khi đọc lần đầu mà theme chưa có dòng) và không bao giờ bị ghi đè hay ghi ngược:
 * hai theme lưu hai chuyên mục khác nhau không ảnh hưởng nhau, và theme `legacy` tiếp tục dùng option cũ như trước. Không xóa option cũ.
 */
final class HomepageSettingsService
{
    public const TYPES = ['category', 'categories', 'number', 'text', 'textarea', 'boolean', 'select', 'management_link'];

    public function __construct(private HomepageBlockRegistry $registry) {}

    /** Definition của một instance trong một theme, hoặc null khi theme không khai báo. */
    public function definitionOf(string $theme, string $instance): ?HomepageBlockDefinition
    {
        $declaration = $this->registry->layout($theme)?->blocks()[$instance] ?? null;

        return $declaration === null ? null : $this->registry->definition($declaration['definition']);
    }

    /**
     * Cấu hình hiệu lực của instance trong theme: mặc định <- bản lưu của CHÍNH theme. Chưa có bản lưu thì dùng giá trị khởi tạo từ option cũ
     * (chỉ đọc, không ghi) để trang chủ không đổi cho tới khi Developer/admin lưu lần đầu.
     *
     * @return array<string, mixed>
     */
    public function read(string $theme, string $instance): array
    {
        $definition = $this->definitionOf($theme, $instance);
        if ($definition === null) {
            return [];
        }
        $values = $definition->defaults();
        $stored = $this->stored($theme, $instance);
        if ($stored === null) {
            return $this->legacyValues($definition, $theme, $values);
        }
        foreach ($definition->settingsSchema() as $field => $schema) {
            if (($schema['type'] ?? '') !== 'management_link' && array_key_exists($field, $stored)) {
                $values[$field] = $stored[$field];
            }
        }

        return $values;
    }

    /**
     * Khởi tạo MỘT LẦN settings của theme từ option cũ cho các instance có `legacyBinding()` mà chưa có dòng. Chạy lại không đè dòng đã có.
     *
     * @return int số dòng được tạo
     */
    public function seedFromLegacy(string $theme): int
    {
        $created = 0;
        $ids = HomepageBlockInstance::query()->pluck('id', 'instance_key');
        foreach ((array) $this->registry->layout($theme)?->blocks() as $instance => $declaration) {
            $definition = $this->registry->definition($declaration['definition']);
            if ($definition === null || $definition->legacyBinding() === [] || ! isset($ids[$instance])) {
                continue;
            }
            $row = HomepageBlockSetting::query()->firstOrNew(['theme_key' => $theme, 'block_instance_id' => $ids[$instance]]);
            if ($row->exists) {
                continue;
            }
            $row->settings = $this->legacyValues($definition, $theme, $definition->defaults());
            $row->save();
            $created++;
        }
        request()->attributes->remove('_homepage_settings_'.$theme);

        return $created;
    }

    /**
     * Kiểm tra và chuẩn hóa dữ liệu gửi lên theo schema. Chỉ field có trong schema được nhận; field khác bị bỏ.
     *
     * @param  array<string, mixed>  $input
     * @return array{values: array<string, mixed>, errors: array<string, string>}
     */
    public function validate(HomepageBlockDefinition $definition, array $input): array
    {
        $values = [];
        $errors = [];
        foreach ($definition->settingsSchema() as $field => $schema) {
            $type = (string) ($schema['type'] ?? '');
            if ($type === 'management_link' || ! in_array($type, self::TYPES, true)) {
                continue;
            }
            $raw = $input[$field] ?? null;
            $required = (bool) ($schema['required'] ?? false);
            switch ($type) {
                case 'category':
                    $id = is_scalar($raw) && trim((string) $raw) !== '' ? $raw : null;
                    if ($id === null) {
                        $values[$field] = null;
                        $required && $errors[$field] = 'required';
                    } elseif (filter_var($id, FILTER_VALIDATE_INT) === false || ! $this->categoryExists((int) $id)) {
                        $errors[$field] = 'category_invalid';
                    } else {
                        $values[$field] = (int) $id;
                    }
                    break;
                case 'categories':
                    $ids = [];
                    foreach ((array) $raw as $item) {
                        if (is_scalar($item) && trim((string) $item) !== '') {
                            $ids[] = $item;
                        }
                    }
                    if (array_filter($ids, fn ($id) => filter_var($id, FILTER_VALIDATE_INT) === false) !== []) {
                        $errors[$field] = 'category_invalid';
                        break;
                    }
                    $ids = array_values(array_unique(array_map('intval', $ids)));
                    if ($ids !== [] && count($this->existingCategories($ids)) !== count($ids)) {
                        $errors[$field] = 'category_invalid';
                    } elseif ($required && $ids === []) {
                        $errors[$field] = 'required';
                    } else {
                        $values[$field] = $ids;
                    }
                    break;
                case 'number':
                    if ($raw === null || trim((string) $raw) === '') {
                        $values[$field] = $schema['default'] ?? null;
                        $required && $errors[$field] = 'required';
                    } elseif (filter_var($raw, FILTER_VALIDATE_INT) === false
                        || (int) $raw < (int) ($schema['min'] ?? PHP_INT_MIN) || (int) $raw > (int) ($schema['max'] ?? PHP_INT_MAX)) {
                        $errors[$field] = 'number_invalid';
                    } else {
                        $values[$field] = (int) $raw;
                    }
                    break;
                case 'text':
                case 'textarea':
                    $text = is_scalar($raw) ? trim((string) $raw) : '';
                    $format = $schema['format'] ?? null;
                    if (mb_strlen($text) > ($type === 'text' ? 255 : 2000)) {
                        $errors[$field] = 'too_long';
                    } elseif ($text !== '' && $format === 'url' && ! preg_match('/^https?:\/\/\S+$/i', $text)) {
                        $errors[$field] = 'url_invalid';
                    } elseif ($text !== '' && $format === 'image_path' && ! preg_match('/^(https?:\/\/|\/)\S+$/i', $text)) {
                        $errors[$field] = 'url_invalid';
                    } elseif ($required && $text === '') {
                        $errors[$field] = 'required';
                    } else {
                        $values[$field] = $text;
                    }
                    break;
                case 'boolean':
                    $values[$field] = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'select':
                    $choice = is_scalar($raw) ? trim((string) $raw) : '';
                    if ($choice === '' && ! $required) {
                        $values[$field] = '';
                    } elseif (! array_key_exists($choice, $this->options($schema))) {
                        $errors[$field] = 'option_invalid';
                    } else {
                        $values[$field] = $choice;
                    }
                    break;
            }
        }

        return ['values' => $values, 'errors' => $errors];
    }

    /**
     * Lưu cấu hình của một instance cho một theme (validate -> bảng cấu hình -> adapter option cũ -> xóa cache layout).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed> giá trị đã lưu
     *
     * @throws InvalidArgumentException message = mã lỗi hoặc `invalid` (kèm lỗi theo field qua getPrevious không dùng; gọi validate() trước để lấy chi tiết)
     */
    public function save(string $theme, string $instance, array $input): array
    {
        $definition = $this->definitionOf($theme, $instance);
        if ($definition === null) {
            throw new InvalidArgumentException('unknown_instance');
        }
        $result = $this->validate($definition, $input);
        if ($result['errors'] !== []) {
            throw new InvalidArgumentException('invalid');
        }
        $instanceId = HomepageBlockInstance::query()->where('instance_key', $instance)->value('id');
        if ($instanceId === null) {
            throw new InvalidArgumentException('not_installed');
        }
        DB::transaction(function () use ($theme, $instanceId, $definition, $result): void {
            HomepageBlockSetting::query()->updateOrCreate(['theme_key' => $theme, 'block_instance_id' => $instanceId], ['settings' => $result['values']]);
        });
        request()->attributes->remove('_homepage_settings_'.$theme);
        app(HomepageLayoutService::class)->flush($theme);

        return $result['values'];
    }

    /**
     * Cảnh báo tham chiếu đã bị xóa (chuyên mục không còn tồn tại).
     *
     * @param  array<string, mixed>  $values
     * @return array<string, list<int>> field => id đã mất
     */
    public function missingReferences(HomepageBlockDefinition $definition, array $values): array
    {
        $missing = [];
        foreach ($definition->settingsSchema() as $field => $schema) {
            $type = $schema['type'] ?? '';
            if (! in_array($type, ['category', 'categories'], true)) {
                continue;
            }
            $ids = array_values(array_filter(array_map('intval', (array) ($values[$field] ?? [])), fn ($id) => $id > 0));
            $gone = array_values(array_diff($ids, $this->existingCategories($ids)));
            if ($gone !== []) {
                $missing[$field] = $gone;
            }
        }

        return $missing;
    }

    /**
     * Dữ liệu để dựng form Cài đặt trang chủ: chỉ cụm đang bật trong kế hoạch render của theme.
     *
     * @param  array{zones: array<string, list<array<string, mixed>>>}  $plan
     * @return list<array<string, mixed>>
     */
    public function forms(string $theme, array $plan): array
    {
        $forms = [];
        foreach ($plan['zones'] as $blocks) {
            foreach ($blocks as $block) {
                $definition = $this->registry->definition($block['definition']);
                if ($definition === null) {
                    continue;
                }
                $values = $block['settings'];
                $fields = [];
                foreach ($definition->settingsSchema() as $field => $schema) {
                    $fields[$field] = $schema + ['options_resolved' => in_array($schema['type'] ?? '', ['select'], true) ? $this->options($schema) : []];
                }
                $forms[] = [
                    'instance' => $block['instance'], 'definition' => $definition, 'fields' => $fields, 'values' => $values,
                    'missing' => $this->missingReferences($definition, $values),
                    'management_url' => $definition->managementRoute() !== null && \Illuminate\Support\Facades\Route::has($definition->managementRoute())
                        ? route($definition->managementRoute()) : null,
                ];
            }
        }

        return $forms;
    }

    /**
     * Lựa chọn của field `select`, lấy từ `options_source` (hiện có: `document_filters`).
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, string> giá trị => nhãn
     */
    public function options(array $schema): array
    {
        if (($schema['options_source'] ?? null) === 'document_filters') {
            if (($memo = request()->attributes->get('_homepage_document_filters')) !== null) {
                return $memo; // several blocks (information, documents) share one list per request
            }
            $options = ['' => (string) __('homepage.settings.document_latest')];
            foreach (\Modules\CmsBackend\Models\LoaiVanBan::orderBy('lvb_ten')->get(['lvb_id', 'lvb_ten']) as $type) {
                $options['lvb:'.$type->lvb_id] = __('homepage.settings.document_type').': '.$type->lvb_ten;
            }
            foreach (\Modules\CmsBackend\Models\NhomVanBan::orderBy('nvb_name')->get(['nvb_id', 'nvb_name']) as $group) {
                $options['nvb:'.$group->nvb_id] = __('homepage.settings.document_group').': '.$group->nvb_name;
            }

            request()->attributes->set('_homepage_document_filters', $options);

            return $options;
        }

        return [];
    }

    /** @return array<string, mixed>|null null khi theme chưa có dòng cho instance */
    private function stored(string $theme, string $instance): ?array
    {
        $key = '_homepage_settings_'.$theme;
        $cache = request()->attributes->get($key);
        if ($cache === null) {
            $cache = [];
            try {
                foreach (HomepageBlockSetting::query()->with('instance:id,instance_key')->where('theme_key', $theme)->get() as $row) {
                    if ($row->instance !== null) {
                        $cache[$row->instance->instance_key] = (array) $row->settings;
                    }
                }
            } catch (\Throwable) {
                $cache = []; // bảng chưa migrate: chỉ dùng mặc định + option cũ
            }
            request()->attributes->set($key, $cache);
        }

        return $cache[$instance] ?? null;
    }

    /**
     * Giá trị khởi tạo từ option cũ cho field có binding (chỉ ĐỌC; option phải đã tồn tại mới được dùng).
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function legacyValues(HomepageBlockDefinition $definition, string $theme, array $values): array
    {
        $binding = $definition->legacyBinding();
        if ($binding === []) {
            return $values;
        }
        $options = Option::query()->whereIn('key', array_values(array_filter($binding, fn ($key) => $key[0] !== '@')))->pluck('value', 'key');
        foreach ($binding as $field => $key) {
            $type = $definition->settingsSchema()[$field]['type'] ?? 'text';
            if ($key === '@featured') {
                $values[$field] = HomeSettings::featuredCategories($theme);

                continue;
            }
            if (! $options->has($key)) {
                continue;
            }
            $value = trim((string) $options[$key]);
            $values[$field] = match ($type) {
                'category' => ctype_digit($value) && (int) $value > 0 ? (int) $value : null,
                'number' => ctype_digit($value) ? (int) $value : ($values[$field] ?? null),
                default => $value,
            };
        }

        return $values;
    }

    private function categoryExists(int $id): bool
    {
        return $id > 0 && Taxonomy::query()->where('taxonomy', 'category')->where('term_taxonomy_id', $id)->exists();
    }

    /**
     * @param  int[]  $ids
     * @return int[]
     */
    private function existingCategories(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return Taxonomy::query()->where('taxonomy', 'category')->whereIn('term_taxonomy_id', $ids)->pluck('term_taxonomy_id')->map(fn ($id) => (int) $id)->all();
    }
}
