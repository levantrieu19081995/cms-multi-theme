<?php

declare(strict_types=1);

namespace Tests\Feature\Survey;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Nền cho các test khảo sát: chỉ chạy trên SQLite :memory: (KHÔNG BAO GIỜ tạo/xóa bảng trên DB thật), dựng schema bằng chính
 * các file migration mới, bảng `options` tối thiểu cho ScreenTheme.
 *
 * Chạy:  DB_CONNECTION=sqlite DB_DATABASE=:memory: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit tests/Feature/Survey
 */
abstract class SurveySqliteTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Cần SQLite :memory: (xem chú thích đầu file); không chạy trên DB thật.');
        }
        Schema::create('options', function ($t) {
            $t->id();
            $t->string('key');
            $t->text('value');
            $t->boolean('autoload')->default(false);
            $t->timestamps();
        });
        foreach (array_merge(glob(base_path('database/migrations/2026_10_31_00000*.php')), glob(base_path('database/migrations/2026_11_01_000002_*.php'))) as $file) {
            (require $file)->up();
        }
        $this->theme('nam-binh-duong');
        $this->withHeader('User-Agent', 'verification-bot');
    }

    protected function theme(string $theme): void
    {
        DB::table('options')->where('key', 'cms_public_theme')->delete();
        DB::table('options')->insert(['key' => 'cms_public_theme', 'value' => $theme]);
        request()->attributes->remove('_cms_screen_options');
        request()->attributes->remove('_cms_screen_paths');
    }

    /** Trang 403 có sẵn của dự án đọc logo từ bảng options; dựng tối thiểu để kiểm tra mã 403 mà không đụng DB thật. */
    protected function withErrorPageSupport(): void
    {
        DB::table('options')->insert(['key' => \App\Enums\OptionEnum::WEB_LOGO->value, 'value' => '/logo.png']);
    }

    protected function user(array $allowed): User
    {
        return new class($allowed) extends User
        {
            public function __construct(private array $allowedLabels = [])
            {
                parent::__construct();
            }

            public function can($abilities, $arguments = []): bool
            {
                return in_array($abilities, $this->allowedLabels, true);
            }

            /** Menu/layout hỏi quyền của người dùng; test không dựng bảng vai trò. */
            public function getAllPermissions(): \Illuminate\Support\Collection
            {
                return collect();
            }
        };
    }
}
