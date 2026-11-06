<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cờ "được sửa trực tiếp bài đã đăng" của từng tài khoản. Chỉ Developer bật/tắt (Developer Settings → Tài khoản); không phải quyền trong
 * màn quản lý vai trò. Mặc định 0 nên không ai có quyền này cho tới khi được bật. Chỉ thêm một cột, tự bỏ qua nếu đã có.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'can_edit_published')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('can_edit_published')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'can_edit_published')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('can_edit_published');
            });
        }
    }
};
