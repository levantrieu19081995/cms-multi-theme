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
        Schema::dropIfExists('ks_tra_loi');

        Schema::create('ks_tra_loi', function (Blueprint $table) {
            $table->id('trl_id');
            $table->unsignedBigInteger('ch_id')->comment('Khóa ngoại đến bảng cau_hoi');
            $table->text('trl_noidung')->comment('Nội dung câu trả lời');
            $table->string('trl_value', 500)->comment('Giá trị lựa chọn (A/B/C/D...)');
            $table->timestamp('trl_created_at')->nullable();
            $table->timestamp('trl_updated_at')->nullable();

            //$table->foreign('ch_id')->references('ch_id')->on('ks_cau_hoi')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ks_tra_loi');
    }
};
