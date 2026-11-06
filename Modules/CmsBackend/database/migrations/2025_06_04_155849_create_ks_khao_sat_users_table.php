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
        Schema::dropIfExists('ks_khao_sat_users');

        Schema::create('ks_khao_sat_users', function (Blueprint $table) {
            $table->id('ksu_id');
            $table->string('ksu_ho_ten', 255)->comment('Họ tên người khảo sát');
            $table->string('ksu_so_dien_thoai', 20)->comment('Số điện thoại');
            $table->timestamp('ksu_created_at')->nullable();
            $table->timestamp('ksu_updated_at')->nullable();
            $table->string('ksu_ket_qua', 255)->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ks_khao_sat_users');
    }
};
