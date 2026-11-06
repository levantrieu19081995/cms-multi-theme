<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/*
 * Mở rộng ks_cau_hoi (câu hỏi) và ks_tra_loi (các lựa chọn). Chỉ cộng thêm cột; cột cũ giữ nguyên.
 * ch_type: 'radio' (cũ) = một lựa chọn; mới: single|multiple|text|textarea|scale|yesno.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ks_cau_hoi')) {
            Schema::create('ks_cau_hoi', function (Blueprint $table) {
                $table->id('ch_id');
                $table->unsignedBigInteger('ks_id');
                $table->string('ch_type', 200)->nullable();
                $table->text('ch_noidung');
                $table->timestamp('ch_created_at')->nullable();
                $table->timestamp('ch_updated_at')->nullable();
                $table->boolean('ch_trang_thai')->default(false);
            });
        }
        if (! Schema::hasTable('ks_tra_loi')) {
            Schema::create('ks_tra_loi', function (Blueprint $table) {
                $table->id('trl_id');
                $table->unsignedBigInteger('ch_id');
                $table->text('trl_noidung');
                $table->string('trl_value', 500);
                $table->timestamp('trl_created_at')->nullable();
                $table->timestamp('trl_updated_at')->nullable();
            });
        }

        Schema::table('ks_cau_hoi', function (Blueprint $table) {
            if (! Schema::hasColumn('ks_cau_hoi', 'ch_ma')) {
                $table->string('ch_ma', 36)->nullable()->unique()->comment('UUID ổn định');
            }
            if (! Schema::hasColumn('ks_cau_hoi', 'nhom_id')) {
                $table->unsignedBigInteger('nhom_id')->nullable()->index();
            }
            if (! Schema::hasColumn('ks_cau_hoi', 'ch_thu_tu')) {
                $table->unsignedInteger('ch_thu_tu')->default(0);
            }
            if (! Schema::hasColumn('ks_cau_hoi', 'ch_bat_buoc')) {
                $table->boolean('ch_bat_buoc')->default(false);
            }
            if (! Schema::hasColumn('ks_cau_hoi', 'ch_mo_ta')) {
                $table->text('ch_mo_ta')->nullable();
            }
            if (! Schema::hasColumn('ks_cau_hoi', 'ch_cau_hinh')) {
                $table->json('ch_cau_hinh')->nullable()->comment('Cấu hình theo kiểu (thang điểm: min,max,nhãn)');
            }
            if (! Schema::hasColumn('ks_cau_hoi', 'ch_deleted_at')) {
                $table->timestamp('ch_deleted_at')->nullable();
            }
            if (! $this->hasIndexOn('ks_cau_hoi', 'ks_id')) {
                $table->index('ks_id', 'ks_cau_hoi_ks_id_index');
            }
        });

        Schema::table('ks_tra_loi', function (Blueprint $table) {
            if (! Schema::hasColumn('ks_tra_loi', 'trl_ma')) {
                $table->string('trl_ma', 36)->nullable()->unique()->comment('UUID ổn định');
            }
            if (! Schema::hasColumn('ks_tra_loi', 'trl_thu_tu')) {
                $table->unsignedInteger('trl_thu_tu')->default(0);
            }
            if (! Schema::hasColumn('ks_tra_loi', 'trl_trang_thai')) {
                $table->boolean('trl_trang_thai')->default(true);
            }
            if (! Schema::hasColumn('ks_tra_loi', 'trl_deleted_at')) {
                $table->timestamp('trl_deleted_at')->nullable();
            }
            if (! $this->hasIndexOn('ks_tra_loi', 'ch_id')) {
                $table->index('ch_id', 'ks_tra_loi_ch_id_index');
            }
        });

        // Bản ghi cũ (nếu có) chưa có mã ổn định: cấp mã, không đụng nội dung.
        foreach (DB::table('ks_cau_hoi')->whereNull('ch_ma')->pluck('ch_id') as $id) {
            DB::table('ks_cau_hoi')->where('ch_id', $id)->update(['ch_ma' => (string) Str::uuid()]);
        }
        foreach (DB::table('ks_tra_loi')->whereNull('trl_ma')->pluck('trl_id') as $id) {
            DB::table('ks_tra_loi')->where('trl_id', $id)->update(['trl_ma' => (string) Str::uuid()]);
        }
    }

    public function down(): void
    {
        // Cố ý không làm gì: không xóa cột/dữ liệu khảo sát khi rollback.
    }

    private function hasIndexOn(string $table, string $column): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['columns'][0] ?? null) === $column) {
                return true;
            }
        }

        return false;
    }
};
