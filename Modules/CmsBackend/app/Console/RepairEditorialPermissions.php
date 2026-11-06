<?php

namespace Modules\CmsBackend\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\CmsBackend\Support\EditorialPermissionHierarchy;

class RepairEditorialPermissions extends Command
{
    protected $signature = 'cms:workflow-repair-permissions';

    protected $description = 'Sửa cây quyền tin/sự kiện và vai trò bị gán chồng trong database riêng Khuyến nông.';

    public function handle(): int
    {
        $database = DB::selectOne('SELECT DATABASE() AS db')->db;
        $expected = config('cmsbackend.editorial_database', 'cms_workspace');
        $socTrang = env('SOCTRANG_DB_DATABASE', 'g2b_cms_caodangsoctrang');

        if ($database !== $expected || $database === $socTrang) {
            $this->error('Database không phải database riêng Khuyến nông. Chưa thay đổi dữ liệu.');
            return self::FAILURE;
        }

        $result = EditorialPermissionHierarchy::repair();
        $this->info("Đã nối {$result['linked']} quyền vào đúng nhóm và gỡ {$result['detached']} lượt gán vai trò bị chồng.");

        return self::SUCCESS;
    }
}
