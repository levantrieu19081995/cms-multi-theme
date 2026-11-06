<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Banner trang chủ: công tắc hiện/ẩn chữ (tiêu đề + mô tả) trên banner. Mặc định 1 = hiện, nên banner đang có giữ nguyên hành vi cũ.
 * Chỉ thêm một cột, không đụng dữ liệu; tự bỏ qua nếu bảng chưa có hoặc cột đã có.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('posts_banners') && ! Schema::hasColumn('posts_banners', 'show_text')) {
            Schema::table('posts_banners', function (Blueprint $table) {
                $table->boolean('show_text')->default(true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('posts_banners', 'show_text')) {
            Schema::table('posts_banners', function (Blueprint $table) {
                $table->dropColumn('show_text');
            });
        }
    }
};
