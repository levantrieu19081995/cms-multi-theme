<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            // Thêm cột slug để chứa giá trị enum ->value (vd: 'khaosat-manager')
            $table->string('slug')->nullable()->after('name');
            // Nếu muốn index để tra cứu nhanh:
            $table->index('slug');
            // KHÔNG đặt unique vội để tránh lỗi khi dữ liệu cũ còn null.
            // Sau khi backfill ổn, nếu muốn có thể thêm unique(['slug','guard_name']) ở migration khác.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }
};
