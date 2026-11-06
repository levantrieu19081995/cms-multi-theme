<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('ks_khao_sat');
        Schema::create('ks_khao_sat', function (Blueprint $table) {
            $table->id('ks_id'); // unsignedBigInteger + primary
            $table->string('ks_ten', 500)->comment('Tên khảo sát');
            $table->timestamp('ks_created_at')->nullable()->comment('Thời điểm tạo');
            $table->timestamp('ks_updated_at')->nullable()->comment('Thời điểm cập nhật');
            $table->boolean('ks_trang_thai')->default(false)->comment('Trạng thái khảo sát');
            $table->text('ks_ghichu')->nullable()->comment('Ghi chú khảo sát');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ks_khao_sat');
    }
};
