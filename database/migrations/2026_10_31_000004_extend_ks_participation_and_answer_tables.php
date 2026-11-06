<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/*
 * ks_khao_sat_users = lượt tham gia; ks_ket_qua = câu trả lời thực tế. Chỉ cộng thêm cột/index.
 * ksu_ho_ten / ksu_so_dien_thoai vẫn NOT NULL (không sửa cột cũ): lưu '' khi ẩn danh, xem ksu_an_danh.
 * ksu_ket_qua, school_key, school_name thuộc Định hướng nghề nghiệp — giữ nguyên, không dùng cho khảo sát chung.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ks_khao_sat_users')) {
            Schema::create('ks_khao_sat_users', function (Blueprint $table) {
                $table->id('ksu_id');
                $table->string('ksu_ho_ten', 255);
                $table->string('ksu_so_dien_thoai', 20);
                $table->string('school_key', 100)->nullable();
                $table->string('school_name', 255)->nullable();
                $table->timestamp('ksu_created_at')->nullable();
                $table->timestamp('ksu_updated_at')->nullable();
                $table->string('ksu_ket_qua', 255)->nullable();
            });
        }
        if (! Schema::hasTable('ks_ket_qua')) {
            Schema::create('ks_ket_qua', function (Blueprint $table) {
                $table->id('kskq_id');
                $table->unsignedBigInteger('ks_id');
                $table->unsignedBigInteger('ch_id');
                $table->unsignedBigInteger('ksu_id');
                $table->string('kskq_trl_value');
            });
        }

        Schema::table('ks_khao_sat_users', function (Blueprint $table) {
            if (! Schema::hasColumn('ks_khao_sat_users', 'ks_id')) {
                $table->unsignedBigInteger('ks_id')->nullable()->index();
            }
            if (! Schema::hasColumn('ks_khao_sat_users', 'ksu_ma')) {
                $table->string('ksu_ma', 36)->nullable()->unique()->comment('Mã lượt tham gia (UUID)');
            }
            if (! Schema::hasColumn('ks_khao_sat_users', 'ksu_trang_thai')) {
                $table->string('ksu_trang_thai', 20)->default('completed')->comment('in_progress|completed');
            }
            if (! Schema::hasColumn('ks_khao_sat_users', 'ksu_hoan_thanh_at')) {
                $table->timestamp('ksu_hoan_thanh_at')->nullable();
            }
            if (! Schema::hasColumn('ks_khao_sat_users', 'ksu_thoi_luong')) {
                $table->unsignedInteger('ksu_thoi_luong')->nullable()->comment('Giây');
            }
            if (! Schema::hasColumn('ks_khao_sat_users', 'ksu_an_danh')) {
                $table->boolean('ksu_an_danh')->default(false);
            }
            if (! Schema::hasColumn('ks_khao_sat_users', 'ksu_email')) {
                $table->string('ksu_email', 255)->nullable();
            }
            if (! Schema::hasColumn('ks_khao_sat_users', 'ksu_dau_vet')) {
                $table->string('ksu_dau_vet', 64)->nullable()->index()->comment('HMAC của cookie trình duyệt — chống gửi lặp');
            }
            if (! Schema::hasColumn('ks_khao_sat_users', 'ksu_ip_hash')) {
                $table->string('ksu_ip_hash', 64)->nullable();
            }
            if (! Schema::hasColumn('ks_khao_sat_users', 'ksu_user_agent')) {
                $table->string('ksu_user_agent', 255)->nullable();
            }
            if (! Schema::hasColumn('ks_khao_sat_users', 'ksu_deleted_at')) {
                $table->timestamp('ksu_deleted_at')->nullable();
            }
        });

        Schema::table('ks_ket_qua', function (Blueprint $table) {
            if (! Schema::hasColumn('ks_ket_qua', 'trl_id')) {
                $table->unsignedBigInteger('trl_id')->nullable()->comment('Lựa chọn thật (null với văn bản)');
            }
            if (! Schema::hasColumn('ks_ket_qua', 'kskq_van_ban')) {
                $table->longText('kskq_van_ban')->nullable()->comment('Văn bản tự do (đầy đủ)');
            }
            if (! Schema::hasColumn('ks_ket_qua', 'kskq_created_at')) {
                $table->timestamp('kskq_created_at')->nullable();
            }
        });
        $this->index('ks_ket_qua', ['ksu_id'], 'ks_ket_qua_ksu_id_index');
        $this->index('ks_ket_qua', ['ks_id', 'ch_id'], 'ks_ket_qua_ks_id_ch_id_index');

        foreach (DB::table('ks_khao_sat_users')->whereNull('ksu_ma')->pluck('ksu_id') as $id) {
            DB::table('ks_khao_sat_users')->where('ksu_id', $id)->update(['ksu_ma' => (string) Str::uuid()]);
        }
    }

    public function down(): void
    {
        // Cố ý không làm gì: không xóa cột/dữ liệu khảo sát khi rollback.
    }

    /** @param string[] $columns */
    private function index(string $table, array $columns, string $name): void
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['columns'] === $columns) {
                return;
            }
        }
        Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
    }
};
