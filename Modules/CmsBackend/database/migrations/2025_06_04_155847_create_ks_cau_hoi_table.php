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
        Schema::dropIfExists('ks_cau_hoi');

        Schema::create('ks_cau_hoi', function (Blueprint $table) {
            $table->id('ch_id');
            $table->unsignedBigInteger('ks_id')->comment('Khóa ngoại đến bảng khảo sát');
            $table->string('ch_type', 200)->nullable()->comment('Loại câu hỏi');
            $table->text('ch_noidung')->comment('Nội dung câu hỏi');
            $table->timestamp('ch_created_at')->nullable();
            $table->timestamp('ch_updated_at')->nullable();
            $table->boolean('ch_trang_thai')->default(false)->comment('Trạng thái');
            //$table->foreign('ks_id')->references('ks_id')->on('ks_khao_sat')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ks_cau_hoi');
    }
};
