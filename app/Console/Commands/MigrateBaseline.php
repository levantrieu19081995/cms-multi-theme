<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Schema;

/**
 * Đánh dấu "đã chạy" các migration CŨ mà cơ sở dữ liệu đã có sẵn kết quả (cơ sở dữ liệu được nhập từ nguồn có sẵn, không tạo bằng migration),
 * để `php artisan migrate` thường chạy được: chỉ còn các migration mới là Pending.
 *
 * Không chạy migration nào, không đổi bảng dữ liệu nào: chỉ thêm tên tệp vào bảng `migrations`.
 *  - Chỉ xét migration cũ hơn mốc --cutoff (mặc định 2026_01_01: các migration nền 2024–2025).
 *  - Migration tạo bảng (Schema::create) chỉ được đánh dấu khi MỌI bảng nó tạo đã tồn tại; thiếu bảng thì để Pending cho migrate chạy thật.
 *  - Không có --force thì chỉ in danh sách sẽ đánh dấu (chạy thử).
 */
final class MigrateBaseline extends Command
{
    protected $signature = 'migrate:baseline {--cutoff=2026_01_01 : Chỉ xét migration có tên nhỏ hơn mốc này} {--force : Ghi thật vào bảng migrations (không có thì chỉ chạy thử)}';

    protected $description = 'Đánh dấu đã chạy các migration cũ mà cơ sở dữ liệu đã có sẵn (không chạy migration, không đổi bảng dữ liệu)';

    public function handle(): int
    {
        /** @var Migrator $migrator */
        $migrator = $this->laravel->make('migrator');
        $write = (bool) $this->option('force');
        $cutoff = (string) $this->option('cutoff');
        $repository = $migrator->getRepository();

        if (! $repository->repositoryExists()) {
            if (! $write) {
                $this->warn('Bảng migrations chưa tồn tại: sẽ được tạo khi chạy với --force.');
            } else {
                $repository->createRepository();
            }
        }
        $ran = $repository->repositoryExists() ? $repository->getRan() : [];
        // Cùng danh sách thư mục với `migrate`: database/migrations và thư mục migration của các module đã đăng ký.
        $files = $migrator->getMigrationFiles(array_merge([database_path('migrations')], $migrator->paths()));

        $mark = [];
        $skip = [];
        foreach ($files as $name => $path) {
            if (in_array($name, $ran, true) || strcmp($name, $cutoff) >= 0) {
                continue;
            }
            $missing = $this->missingTables((string) file_get_contents($path));
            if ($missing === []) {
                $mark[] = $name;
            } else {
                $skip[$name] = $missing;
            }
        }

        $this->info(sprintf('Cũ hơn %s và chưa ghi nhận: %d tệp. Sẽ đánh dấu đã chạy: %d. Để Pending (thiếu bảng): %d.', $cutoff, count($mark) + count($skip), count($mark), count($skip)));
        foreach ($mark as $name) {
            $this->line('  + '.$name);
        }
        foreach ($skip as $name => $missing) {
            $this->warn('  ! '.$name.'  (thiếu bảng: '.implode(', ', $missing).') -> giữ Pending');
        }

        if (! $write) {
            $this->comment('Chạy thử: chưa ghi gì. Thêm --force để ghi vào bảng migrations.');

            return self::SUCCESS;
        }
        if ($mark !== []) {
            $batch = $repository->getNextBatchNumber();
            foreach ($mark as $name) {
                $repository->log($name, $batch);
            }
        }
        $this->info('Đã ghi '.count($mark).' migration vào bảng migrations. Giờ có thể chạy `php artisan migrate`.');

        return self::SUCCESS;
    }

    /** @return list<string> các bảng mà migration này tạo (Schema::create) nhưng chưa tồn tại */
    private function missingTables(string $source): array
    {
        preg_match_all("/Schema::create\(\s*['\"]([A-Za-z0-9_]+)['\"]/", $source, $matches);

        return array_values(array_filter(array_unique($matches[1]), fn (string $table): bool => ! Schema::hasTable($table)));
    }
}
