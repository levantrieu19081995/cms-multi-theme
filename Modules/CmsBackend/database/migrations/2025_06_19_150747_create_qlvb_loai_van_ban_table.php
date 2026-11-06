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
        if (!Schema::hasTable('qlvb_loai_van_ban')) {
            Schema::create('qlvb_loai_van_ban', function (Blueprint $table) {
                $table->id('lvb_id');
                $table->string('lvb_ma', 10);
                $table->string('lvb_ten', 255);
                $table->bigInteger('user_id');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qlvb_loai_van_ban');
    }
};
