<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liên hệ – Góp ý: thêm `address` (nullable, bản ghi cũ không có) và mở rộng `message` từ string sang text.
 * An toàn với dữ liệu hiện có: chỉ thêm cột nullable và nới kiểu cột; chạy lại không lỗi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lienhe')) {
            return;
        }

        if (! Schema::hasColumn('lienhe', 'address')) {
            Schema::table('lienhe', function (Blueprint $table) {
                $table->string('address', 255)->nullable()->after('phone');
            });
        }

        if (! in_array(strtolower(Schema::getColumnType('lienhe', 'message')), ['text', 'mediumtext', 'longtext'], true)) {
            Schema::table('lienhe', function (Blueprint $table) {
                $table->text('message')->change();
            });
        }
    }

    /** Không thu hẹp cột/xóa cột: sẽ làm mất dữ liệu đã nhận. */
    public function down(): void {}
};
