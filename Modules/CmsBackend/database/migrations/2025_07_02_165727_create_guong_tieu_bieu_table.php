<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guong_tieu_bieu', function (Blueprint $table) {
            $table->id();
            $table->string('title', 4000)->nullable();
            $table->longText('content')->nullable();
            $table->string('excerpt', 5000)->nullable();
            $table->integer('show_home_page')->default(0);
            $table->decimal('rating', 2, 1)->nullable();   // ví dụ: 4.5
            $table->string('role')->nullable();            // vai trò, phân loại
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guong_tieu_bieu');
    }
};
