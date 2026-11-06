<?php

declare(strict_types=1);

namespace Modules\CmsFrontend\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Modules\CmsFrontend\Homepage\HomepageBlockRegistry;
use Modules\CmsFrontend\Homepage\HomepageLayoutService;

/**
 * Import bố cục mặc định (trang chủ hiện tại) của các theme vào database. Chạy lại an toàn: chỉ thêm dòng còn thiếu, không đè bố cục đã chỉnh.
 * Dùng --reset để khôi phục bố cục mặc định của một theme (cấu hình nội dung giữ nguyên).
 */
final class HomepageLayoutInstallCommand extends Command
{
    protected $signature = 'homepage:layout-install {theme? : Khóa theme (bỏ trống = tất cả)} {--reset : Khôi phục bố cục mặc định của theme (cần chỉ định theme)}';

    protected $description = 'Import bố cục trang chủ mặc định của các theme (Homepage Layout Manager).';

    public function handle(HomepageLayoutService $layouts, HomepageBlockRegistry $registry): int
    {
        $theme = $this->argument('theme');
        if ($theme !== null && $registry->layout((string) $theme) === null) {
            $this->error('Theme không được hỗ trợ: '.$theme);

            return self::FAILURE;
        }
        if ($registry->selfCheck() !== []) {
            foreach ($registry->selfCheck() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        try {
            if ($this->option('reset')) {
                if ($theme === null) {
                    $this->error('--reset cần chỉ định theme.');

                    return self::FAILURE;
                }
                $layouts->resetToDefault((string) $theme);
                $this->info('Đã khôi phục bố cục mặc định của '.$theme.'.');

                return self::SUCCESS;
            }
            $created = $layouts->installDefaults($theme === null ? null : (string) $theme);
        } catch (InvalidArgumentException $e) {
            $this->error('Chưa thể thực hiện ('.$e->getMessage().'). Hãy chạy migration của Homepage Layout Manager trước.');

            return self::FAILURE;
        }
        $this->info($created.' dòng bố cục được thêm (0 = đã đủ, không thay đổi).');

        return self::SUCCESS;
    }
}
