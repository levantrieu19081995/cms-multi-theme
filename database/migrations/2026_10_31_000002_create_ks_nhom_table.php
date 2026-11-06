<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Nhóm/phần câu hỏi của một khảo sát. Bảng mới; không FK (theo quy ước của nhóm bảng ks_*). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ks_nhom')) {
            return;
        }
        Schema::create('ks_nhom', function (Blueprint $table) {
            $table->id('nhom_id');
            $table->unsignedBigInteger('ks_id')->index();
            $table->string('nhom_ma', 36)->unique()->comment('UUID ổn định');
            $table->string('nhom_ten', 255);
            $table->text('nhom_mo_ta')->nullable();
            $table->unsignedInteger('nhom_thu_tu')->default(0);
            $table->timestamp('nhom_created_at')->nullable();
            $table->timestamp('nhom_updated_at')->nullable();
            $table->timestamp('nhom_deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        // Cố ý không làm gì: không xóa dữ liệu khảo sát khi rollback.
    }
};
