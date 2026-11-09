<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Định hướng nghề nghiệp (RIASEC) trên nền Khảo sát. Chỉ CỘNG THÊM, chạy lại an toàn (guard hasTable/hasColumn), không FK, không drop.
 *  - ks_khao_sat_users.ksu_diem  : điểm từng nhóm R/I/A/S/E/C của một lượt (JSON). Mã Holland ghi vào cột cũ ksu_ket_qua.
 *  - ks_career_group             : tên/mô tả hiển thị của 6 nhóm do quản trị chỉnh (thiếu dòng thì dùng chữ mặc định trong lang/career.php).
 *  - ks_career_major             : ngành/nghề gợi ý do trường tự khai báo, gắn mã RIASEC 1–3 chữ cái. Không có dữ liệu mẫu trong migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ks_khao_sat_users') && ! Schema::hasColumn('ks_khao_sat_users', 'ksu_diem')) {
            Schema::table('ks_khao_sat_users', function (Blueprint $table) {
                $table->text('ksu_diem')->nullable()->comment('JSON điểm theo nhóm RIASEC');
            });
        }

        if (! Schema::hasTable('ks_career_group')) {
            Schema::create('ks_career_group', function (Blueprint $table) {
                $table->char('cg_ma', 1)->primary()->comment('R|I|A|S|E|C');
                $table->string('cg_ten', 255)->nullable();
                $table->text('cg_mo_ta')->nullable();
                $table->timestamp('cg_updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('ks_career_major')) {
            Schema::create('ks_career_major', function (Blueprint $table) {
                $table->id('cm_id');
                $table->string('cm_ten', 255);
                $table->string('cm_ma_riasec', 3)->index()->comment('1–3 chữ cái R/I/A/S/E/C, chữ đầu quan trọng nhất');
                $table->text('cm_mo_ta')->nullable();
                $table->string('cm_url', 500)->nullable();
                $table->unsignedInteger('cm_thu_tu')->default(0);
                $table->boolean('cm_trang_thai')->default(true);
                $table->unsignedBigInteger('cm_created_by')->nullable();
                $table->timestamp('cm_created_at')->nullable();
                $table->timestamp('cm_updated_at')->nullable();
                $table->timestamp('cm_deleted_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        // Cố ý không làm gì: không xóa dữ liệu khảo sát khi rollback.
    }
};
