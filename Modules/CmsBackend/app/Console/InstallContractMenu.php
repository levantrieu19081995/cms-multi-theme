<?php

namespace Modules\CmsBackend\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstallContractMenu extends Command
{
    public const MENU_NAME = 'Khuyến nông – Menu chính';

    protected $signature = 'cms:menu-install {--dry-run : Chỉ in thay đổi, không ghi database}';
    protected $description = 'Tạo menu chính Khuyến nông theo phụ lục hợp đồng 134/HĐ-CNPM-PM; chỉ ghi database riêng Khuyến nông.';

    private array $log = [];

    /** Draft pages the editors fill in later; menu hides them until published. */
    private const PAGES = [
        'trang-thong-tin-dien-tu' => 'Trang thông tin điện tử',
        'tong-quan-trung-tam-khuyen-nong-va-dich-vu-nong-nghiep' => 'Tổng quan Trung tâm Khuyến nông và Dịch vụ Nông nghiệp',
        'so-do-to-chuc' => 'Sơ đồ tổ chức',
        'danh-ba-dien-thoai-trung-tam' => 'Danh bạ điện thoại Trung tâm Khuyến nông và Dịch vụ Nông nghiệp',
        'danh-ba-nhan-vien-quan-ly-cong-trinh-thuy-loi' => 'Danh bạ Nhân viên quản lý công trình thủy lợi',
        'danh-ba-hop-tac-xa-co-so-san-xuat' => 'Danh bạ Hợp tác xã, cơ sở sản xuất',
        'logo-khuyen-nong-va-dich-vu-nong-nghiep' => 'Logo Khuyến nông và Dịch vụ Nông nghiệp',
    ];

    private const TECHNICAL_CATEGORIES = [
        'tai-lieu-trong-trot' => 'Tài liệu Trồng trọt',
        'tai-lieu-chan-nuoi' => 'Tài liệu Chăn nuôi',
        'tai-lieu-thuy-san' => 'Tài liệu Thủy sản',
        'tai-lieu-ky-nang-va-khac' => 'Tài liệu Kỹ năng và Khác',
    ];

    /** nvb_name is varchar(50); the menu shows the full title. */
    private const DOCUMENT_GROUPS = [
        'van-ban-trung-uong' => ['Văn bản Trung ương', 'Văn bản Trung ương'],
        'van-ban-dia-phuong' => ['Văn bản của địa phương', 'Văn bản của địa phương'],
        'van-ban-trung-tam' => ['Văn bản Trung tâm KN&DVNN', 'Văn bản Trung tâm Khuyến nông và Dịch vụ Nông nghiệp'],
    ];

    public function handle(): int
    {
        $database = DB::selectOne('SELECT DATABASE() AS db')->db;
        if ($database !== config('cmsbackend.editorial_database', 'cms_workspace') || $database === env('SOCTRANG_DB_DATABASE', 'g2b_cms_caodangsoctrang')) {
            $this->error('Database không phải database riêng Khuyến nông. Chưa ghi dữ liệu.');
            return self::FAILURE;
        }
        $dryRun = (bool) $this->option('dry-run');
        $adminId = (int) (DB::table('users')->where('username', 'admin')->value('id') ?? 1);

        DB::beginTransaction();
        try {
            $pages = [];
            foreach (self::PAGES as $slug => $title) $pages[$slug] = $this->page($slug, $title, $adminId);
            $technical = [];
            foreach (self::TECHNICAL_CATEGORIES as $slug => $name) $technical[$slug] = $this->category($slug, $name);
            $groups = [];
            foreach (self::DOCUMENT_GROUPS as $slug => [$name]) $groups[$slug] = $this->documentGroup($slug, $name, $adminId);
            foreach ([36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49] as $termId) {
                if (!DB::table('terms')->where('term_id', $termId)->whereNull('deleted_at')->exists()) {
                    throw new \RuntimeException("Thiếu chuyên mục term_id={$termId}; dừng để không tạo menu trỏ sai.");
                }
            }

            $tree = [
                ['url', 'Trang chủ', '/'],
                ['url', 'Giới thiệu', '#', [
                    ['page', 'Trang thông tin điện tử', $pages['trang-thong-tin-dien-tu']],
                    ['page', 'Tổng quan Trung tâm Khuyến nông và Dịch vụ Nông nghiệp', $pages['tong-quan-trung-tam-khuyen-nong-va-dich-vu-nong-nghiep']],
                    ['page', 'Sơ đồ tổ chức', $pages['so-do-to-chuc']],
                    ['category', 'Lịch làm việc Ban giám đốc', 49],
                    ['category', 'Các thông báo/lịch sự kiện', 47],
                ]],
                ['url', 'Tin tức', '/tin-tuc', [
                    ['category', 'Tin ngành nông nghiệp và môi trường', 36],
                    ['category', 'Tin Khuyến nông và Dịch vụ Nông nghiệp địa bàn Thành phố Cần Thơ', 38],
                    ['category', 'Tin Khuyến nông và Dịch vụ Nông nghiệp các tỉnh thành', 37],
                    ['category', 'Mô hình khuyến nông, nông nghiệp hiệu quả', 39],
                    ['category', 'Khoa học kỹ thuật mới và khuyến cáo', 40],
                    ['category', 'Phổ biến chính sách và kêu gọi đầu tư', 41],
                    ['category', 'Sản phẩm dịch vụ khuyến nông, nông nghiệp', 42],
                    ['category', 'Bản tin giá nông sản địa phương', 43],
                    ['category', 'Sản phẩm chủ lực, OCOP địa phương', 44],
                    ['category', 'Trang văn nghệ khuyến nông', 45],
                ]],
                ['url', 'Thư viện video, ảnh', '#', [
                    ['url', 'Video Khuyến nông', '/thu-vien-video'],
                    ['category', 'Bản tin Khuyến nông (Bản in – Đã xuất bản)', 46],
                    ['url', 'Thư viện ảnh', '/thu-vien-hinh-anh'],
                ]],
                ['url', 'Tài liệu kỹ thuật', '#', array_map(
                    fn ($slug) => ['category', self::TECHNICAL_CATEGORIES[$slug], $technical[$slug]],
                    array_keys(self::TECHNICAL_CATEGORIES)
                )],
                ['url', 'Văn bản ban hành', '/van-ban', [
                    ...array_map(fn ($slug) => ['docs', self::DOCUMENT_GROUPS[$slug][1], $groups[$slug]], array_keys(self::DOCUMENT_GROUPS)),
                    ['category', 'Các biểu mẫu văn bản', 48],
                ]],
                ['url', 'Liên hệ', '#', [
                    ['url', 'Thông tin liên hệ', '/lien-he'],
                    ['page', 'Danh bạ điện thoại Trung tâm Khuyến nông và Dịch vụ Nông nghiệp', $pages['danh-ba-dien-thoai-trung-tam']],
                    ['page', 'Danh bạ Nhân viên quản lý công trình thủy lợi', $pages['danh-ba-nhan-vien-quan-ly-cong-trinh-thuy-loi']],
                    ['page', 'Danh bạ Hợp tác xã, cơ sở sản xuất', $pages['danh-ba-hop-tac-xa-co-so-san-xuat']],
                    ['page', 'Logo Khuyến nông và Dịch vụ Nông nghiệp', $pages['logo-khuyen-nong-va-dich-vu-nong-nghiep']],
                ]],
            ];

            $menuId = DB::table('menus')->where('name', self::MENU_NAME)->whereNull('deleted_at')->value('id');
            if ($menuId) {
                $removed = DB::table('menu_items')->where('menu_id', $menuId)->delete();
                $this->log[] = "Làm mới menu #{$menuId}: xóa {$removed} mục cũ của chính menu này";
            } else {
                $menuId = DB::table('menus')->insertGetId(['name' => self::MENU_NAME, 'description' => 'Menu đầu trang theo phụ lục hợp đồng 134/HĐ-CNPM-PM', 'created_at' => now(), 'updated_at' => now()]);
                $this->log[] = "Tạo menu #{$menuId} \"" . self::MENU_NAME . '"';
            }
            $count = $this->items($menuId, $tree, 0, '');
            $this->log[] = "Tạo {$count} mục menu";

            $previous = DB::table('options')->where('key', 'main_menu')->value('value');
            DB::table('options')->updateOrInsert(['key' => 'main_menu'], ['value' => (string) $menuId, 'autoload' => 1, 'updated_at' => now()]);
            $this->log[] = "options.main_menu: {$previous} → {$menuId} (menu cũ giữ nguyên)";

            foreach ($this->log as $line) $this->line(' - ' . $line);
            if ($dryRun) {
                DB::rollBack();
                $this->warn('Chạy thử: đã hoàn tác, chưa ghi gì.');
            } else {
                DB::commit();
                $this->info('Đã cài menu chính Khuyến nông tại ' . $database . '.');
            }
            return self::SUCCESS;
        } catch (\Throwable $error) {
            DB::rollBack();
            $this->error($error->getMessage());
            return self::FAILURE;
        }
    }

    private function items(int $menuId, array $items, int $parentId, string $parentPath): int
    {
        $count = 0;
        foreach (array_values($items) as $ordinal => $item) {
            [$type, $title, $target] = $item;
            $id = DB::table('menu_items')->insertGetId([
                'menu_id' => $menuId, 'title' => $title, 'parent_id' => $parentId, 'treepath' => '',
                'url' => $type === 'url' ? $target : $title, 'type' => $type, 'ref_id' => $type === 'url' ? 0 : $target,
                'target' => '_self', 'ordinal' => $ordinal, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('menu_items')->where('id', $id)->update(['treepath' => $parentPath . '/' . $id]);
            $count += 1 + $this->items($menuId, $item[3] ?? [], $id, $parentPath . '/' . $id);
        }
        return $count;
    }

    private function page(string $slug, string $title, int $authorId): int
    {
        $id = DB::table('posts')->where('slug', $slug)->where('post_type', 'page')->whereNull('deleted_at')->value('id');
        if ($id) return (int) $id;
        if (DB::table('posts')->where('slug', $slug)->exists()) {
            throw new \RuntimeException("Slug \"{$slug}\" đã thuộc một bài không phải trang; dừng để không trùng đường dẫn.");
        }
        $id = DB::table('posts')->insertGetId([
            'title' => $title, 'slug' => $slug, 'content' => '', 'post_type' => 'page', 'status' => 'draft',
            'author_id' => $authorId, 'comment_status' => 'disabled', 'publish_type' => 'publish_now', 'format' => 'default',
            'views' => 0, 'show_home_page' => 0,'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->log[] = "Tạo trang nháp #{$id} \"{$title}\"";
        return $id;
    }

    private function category(string $slug, string $name): int
    {
        $termId = DB::table('terms')->where('slug', $slug)->whereNull('deleted_at')->value('term_id');
        if (!$termId) {
            $termId = DB::table('terms')->insertGetId(['name' => $name, 'slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
            $this->log[] = "Tạo chuyên mục #{$termId} \"{$name}\"";
        }
        if (!DB::table('term_taxonomy')->where('term_id', $termId)->where('taxonomy', 'category')->exists()) {
            DB::table('term_taxonomy')->insert(['term_id' => $termId, 'taxonomy' => 'category', 'description' => $name, 'loai_chuyen_muc' => 'cong_bo', 'parent' => 0, 'count' => 0]);
        }
        return (int) $termId;
    }

    private function documentGroup(string $slug, string $name, int $userId): int
    {
        $id = DB::table('qlvb_nhom_van_ban')->where('nvb_slug', $slug)->whereNull('deleted_at')->value('nvb_id');
        if ($id) return (int) $id;
        $id = DB::table('qlvb_nhom_van_ban')->insertGetId([
            'nvb_name' => Str::limit($name, 50, ''), 'nvb_description' => $name, 'nvb_slug' => $slug,
            'user_id' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->log[] = "Tạo nhóm văn bản #{$id} \"{$name}\"";
        return $id;
    }
}
