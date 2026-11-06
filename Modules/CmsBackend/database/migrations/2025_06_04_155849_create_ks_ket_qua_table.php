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
        Schema::dropIfExists('ks_ket_qua');

        Schema::create('ks_ket_qua', function (Blueprint $table) {
            $table->id('kskq_id');
            $table->unsignedBigInteger('ks_id')->comment('FK đến bảng khảo sát');
            $table->unsignedBigInteger('ch_id')->comment('FK đến bảng câu hỏi');
            $table->unsignedBigInteger('ksu_id')->comment('FK đến người khảo sát');
            $table->string('kskq_trl_value')->comment('Giá trị người dùng chọn');
            //$table->foreign('ks_id')->references('ks_id')->on('ks_khao_sat')->onDelete('cascade');
           // $table->foreign('ch_id')->references('ch_id')->on('ks_cau_hoi')->onDelete('cascade');
            //$table->foreign('ksu_id')->references('ksu_id')->on('ks_khao_sat_users')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ks_ket_qua');
    }
};
