<?php

namespace Modules\CmsBackend\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\CmsBackend\Support\HomeSettings;

class InstallHomeBlocks extends Command
{
    protected $signature = 'cms:home-install {--dry-run : Chỉ in thay đổi, không ghi database}';
    protected $description = 'Tạo chuyên mục và cấu hình mặc định cho các khối trang chủ theo PLHĐ (lịch làm việc, truy xuất nguồn gốc); chỉ ghi database riêng Khuyến nông.';

    private const DEPARTMENT_SLUG = 'lich-lam-viec-lanh-dao-so-nn-mt';
    private const DEPARTMENT_NAME = 'Lịch làm việc Lãnh đạo Sở NN&MT';
    private const BOARD_SLUG = 'lich-cong-tac-bg';
    private const FOOTER_MENU_NAME = 'Khuyến nông – Liên kết nhanh chân trang';

    public function handle(): int
    {
        $database = DB::selectOne('SELECT DATABASE() AS db')->db;
        if ($database !== config('cmsbackend.editorial_database', 'cms_workspace') || $database === env('SOCTRANG_DB_DATABASE', 'g2b_cms_caodangsoctrang')) {
            $this->error('Database không phải database riêng Khuyến nông. Chưa ghi dữ liệu.');
            return self::FAILURE;
        }
        $log = [];
        DB::beginTransaction();
        try {
            $termId = DB::table('terms')->where('slug', self::DEPARTMENT_SLUG)->whereNull('deleted_at')->value('term_id');
            if (!$termId) {
                $termId = DB::table('terms')->insertGetId(['name' => self::DEPARTMENT_NAME, 'slug' => self::DEPARTMENT_SLUG, 'created_at' => now(), 'updated_at' => now()]);
                $log[] = "Tạo chuyên mục #{$termId} \"" . self::DEPARTMENT_NAME . '"';
            }
            $departmentTaxonomy = DB::table('term_taxonomy')->where('term_id', $termId)->where('taxonomy', 'category')->value('term_taxonomy_id');
            if (!$departmentTaxonomy) {
                $departmentTaxonomy = DB::table('term_taxonomy')->insertGetId(['term_id' => $termId, 'taxonomy' => 'category', 'description' => self::DEPARTMENT_NAME, 'loai_chuyen_muc' => 'cong_bo', 'parent' => 0, 'count' => 0], 'term_taxonomy_id');
            }
            $boardTaxonomy = DB::table('term_taxonomy')->join('terms', 'terms.term_id', '=', 'term_taxonomy.term_id')
                ->where('terms.slug', self::BOARD_SLUG)->whereNull('terms.deleted_at')->where('term_taxonomy.taxonomy', 'category')
                ->value('term_taxonomy.term_taxonomy_id');

            // Only fill defaults; values an editor already chose in Settings are kept.
            foreach ([HomeSettings::SCHEDULE_DEPARTMENT => $departmentTaxonomy, HomeSettings::SCHEDULE_BOARD => $boardTaxonomy] as $key => $default) {
                $current = DB::table('options')->where('key', $key)->value('value');
                if (filled($current) || !$default) continue;
                DB::table('options')->updateOrInsert(['key' => $key], ['value' => (string) $default, 'autoload' => 1, 'updated_at' => now(), 'created_at' => now()]);
                $log[] = "options.{$key} = {$default}";
            }
            foreach ([HomeSettings::TRACE_MARKET_URL, HomeSettings::TRACE_MARKET_IMAGE, HomeSettings::TRACE_PORTAL_URL, HomeSettings::TRACE_PORTAL_IMAGE] as $key) {
                if (DB::table('options')->where('key', $key)->exists()) continue;
                DB::table('options')->insert(['key' => $key, 'value' => '', 'autoload' => 1, 'created_at' => now(), 'updated_at' => now()]);
                $log[] = "options.{$key} = '' (nhập ở Cài đặt trang chủ)";
            }

            // Footer icon links (Dịch vụ, Sự kiện, Hỏi đáp moved out of the main menu); editable in the Menu screen.
            $footerMenuId = DB::table('menus')->where('name', self::FOOTER_MENU_NAME)->whereNull('deleted_at')->value('id');
            if (!$footerMenuId) {
                $footerMenuId = DB::table('menus')->insertGetId(['name' => self::FOOTER_MENU_NAME, 'description' => 'Liên kết nhanh dạng icon ở chân trang', 'created_at' => now(), 'updated_at' => now()]);
                foreach ([['Dịch vụ', '/dich-vu'], ['Sự kiện', '/su-kien'], ['Hỏi đáp', '/hoi-dap']] as $ordinal => [$title, $url]) {
                    $id = DB::table('menu_items')->insertGetId([
                        'menu_id' => $footerMenuId, 'title' => $title, 'parent_id' => 0, 'treepath' => '', 'url' => $url, 'type' => 'url',
                        'ref_id' => 0, 'target' => '_self', 'ordinal' => $ordinal, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    DB::table('menu_items')->where('id', $id)->update(['treepath' => '/' . $id]);
                }
                $log[] = "Tạo menu chân trang #{$footerMenuId} \"" . self::FOOTER_MENU_NAME . '" (3 mục)';
            }
            if (!filled(DB::table('options')->where('key', HomeSettings::FOOTER_MENU)->value('value'))) {
                DB::table('options')->updateOrInsert(['key' => HomeSettings::FOOTER_MENU], ['value' => (string) $footerMenuId, 'autoload' => 1, 'updated_at' => now(), 'created_at' => now()]);
                $log[] = 'options.' . HomeSettings::FOOTER_MENU . " = {$footerMenuId}";
            }

            foreach ($log ?: ['Không có thay đổi'] as $line) $this->line(' - ' . $line);
            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->warn('Chạy thử: đã hoàn tác, chưa ghi gì.');
            } else {
                DB::commit();
                $this->info('Đã cài cấu hình khối trang chủ tại ' . $database . '.');
            }
            return self::SUCCESS;
        } catch (\Throwable $error) {
            DB::rollBack();
            $this->error($error->getMessage());
            return self::FAILURE;
        }
    }
}
