<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Khảo sát dùng chung — mở rộng ks_khao_sat. Chỉ CỘNG THÊM: bảng cũ không bị xóa/đổi tên/đổi kiểu cột.
 * Không có project_key/theme_key (1 khách = 1 theme = 1 DB). Chạy lại an toàn nhờ guard hasTable/hasColumn.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ks_khao_sat')) {
            // Schema nền cũ, chỉ tạo khi bảng chưa tồn tại (không bao giờ drop).
            Schema::create('ks_khao_sat', function (Blueprint $table) {
                $table->id('ks_id');
                $table->string('ks_ten', 500);
                $table->timestamp('ks_created_at')->nullable();
                $table->timestamp('ks_updated_at')->nullable();
                $table->boolean('ks_trang_thai')->default(false);
                $table->text('ks_ghichu')->nullable();
            });
        }

        $added = ! Schema::hasColumn('ks_khao_sat', 'ks_status');

        Schema::table('ks_khao_sat', function (Blueprint $table) {
            if (! Schema::hasColumn('ks_khao_sat', 'ks_ma')) {
                $table->string('ks_ma', 120)->nullable()->unique()->comment('Mã/slug công khai');
            }
            if (! Schema::hasColumn('ks_khao_sat', 'ks_mo_ta_ngan')) {
                $table->string('ks_mo_ta_ngan', 500)->nullable();
            }
            if (! Schema::hasColumn('ks_khao_sat', 'ks_gioi_thieu')) {
                $table->longText('ks_gioi_thieu')->nullable()->comment('Nội dung giới thiệu');
            }
            if (! Schema::hasColumn('ks_khao_sat', 'ks_loai')) {
                $table->string('ks_loai', 50)->default('general');
            }
            if (! Schema::hasColumn('ks_khao_sat', 'ks_status')) {
                $table->string('ks_status', 20)->default('draft')->index()->comment('draft|open|paused|ended');
            }
            if (! Schema::hasColumn('ks_khao_sat', 'ks_bat_dau')) {
                $table->dateTime('ks_bat_dau')->nullable();
            }
            if (! Schema::hasColumn('ks_khao_sat', 'ks_ket_thuc')) {
                $table->dateTime('ks_ket_thuc')->nullable();
            }
            if (! Schema::hasColumn('ks_khao_sat', 'ks_cho_phep_an_danh')) {
                $table->boolean('ks_cho_phep_an_danh')->default(true);
            }
            if (! Schema::hasColumn('ks_khao_sat', 'ks_yeu_cau_thong_tin')) {
                $table->boolean('ks_yeu_cau_thong_tin')->default(false)->comment('Yêu cầu họ tên + liên hệ');
            }
            if (! Schema::hasColumn('ks_khao_sat', 'ks_so_lan_gui')) {
                $table->unsignedSmallInteger('ks_so_lan_gui')->default(1)->comment('0 = không giới hạn');
            }
            if (! Schema::hasColumn('ks_khao_sat', 'ks_noi_dung_hoan_thanh')) {
                $table->text('ks_noi_dung_hoan_thanh')->nullable();
            }
            if (! Schema::hasColumn('ks_khao_sat', 'ks_created_by')) {
                $table->unsignedBigInteger('ks_created_by')->nullable();
            }
            if (! Schema::hasColumn('ks_khao_sat', 'ks_updated_by')) {
                $table->unsignedBigInteger('ks_updated_by')->nullable();
            }
            if (! Schema::hasColumn('ks_khao_sat', 'ks_deleted_at')) {
                $table->timestamp('ks_deleted_at')->nullable()->index();
            }
        });

        if ($added) {
            // Khảo sát cũ có cờ bool: giữ ngữ nghĩa (bật = đang mở). Chỉ ghi vào cột MỚI.
            DB::table('ks_khao_sat')->where('ks_trang_thai', true)->update(['ks_status' => 'open']);
        }
    }

    public function down(): void
    {
        // Cố ý không làm gì: không xóa cột/dữ liệu khảo sát khi rollback.
    }
};
