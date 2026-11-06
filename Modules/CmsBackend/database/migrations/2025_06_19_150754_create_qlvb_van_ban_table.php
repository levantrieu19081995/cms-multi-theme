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
        if (!Schema::hasTable('qlvb_van_ban')) {
            Schema::create('qlvb_van_ban', function (Blueprint $table) {
                $table->id('vb_id');
                $table->string('vb_so_hieu', 50);
                $table->string('vb_trich_yeu', 4000);
                $table->date('vb_ngay_ban_hanh');
                $table->string('vb_file_url', 255);
                $table->integer('lvb_id');
                $table->integer('nvb_id');
                $table->bigInteger('user_id');
                $table->timestamps();
                $table->softDeletes();
                $table->date('vb_ngay_hieu_luc');
                $table->date('vb_ngay_cong_bo');
                $table->string('vb_co_quan_ban_hanh', 50);
                $table->integer('vb_thoi_gian_luu_tru');
                $table->integer('vb_lan_ban_hanh');
            });
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qlvb_van_ban');
    }
};