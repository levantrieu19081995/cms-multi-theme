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
        Schema::create('dichvu', function (Blueprint $table) {
            $table->id();
            $table->string('icon')->nullable();      // Đường dẫn hoặc class icon
            $table->string('title');                 // Tiêu đề
            $table->string('link');                  // Link
            $table->boolean('status')->default(1);   // Trạng thái (1: hiện, 0: ẩn)
            $table->softDeletes();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dichvu');
    }
};
