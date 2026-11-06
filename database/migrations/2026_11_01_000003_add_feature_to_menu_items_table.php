<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menu loại "Chức năng hệ thống": tham chiếu bằng (feature_key, feature_target), URL sinh từ route trong Feature Registry.
 * Tương thích dữ liệu cũ: chỉ thêm 2 cột nullable và giá trị 'feature' vào ENUM `type`; các dòng menu hiện có không đổi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('menu_items')) {
            return;
        }
        Schema::table('menu_items', function (Blueprint $table) {
            if (! Schema::hasColumn('menu_items', 'feature_key')) {
                $table->string('feature_key', 100)->nullable();
            }
            if (! Schema::hasColumn('menu_items', 'feature_target')) {
                $table->string('feature_target', 100)->nullable();
            }
        });

        // ENUM chỉ có ở MySQL/MariaDB (SQLite của bộ test lưu dạng văn bản). Thêm giá trị mới nếu chưa có.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $type = (string) (DB::selectOne("SHOW COLUMNS FROM `menu_items` LIKE 'type'")->Type ?? '');
            if ($type !== '' && ! str_contains($type, "'feature'")) {
                DB::statement("ALTER TABLE `menu_items` MODIFY COLUMN `type` ENUM('url', 'category', 'tag', 'post', 'page', 'image', 'video', 'docs', 'feature') NOT NULL DEFAULT 'url'");
            }
        }
    }

    public function down(): void
    {
        // Cố ý để trống: gỡ cột/giá trị ENUM sẽ làm mất tham chiếu của các menu đã tạo.
    }
};
