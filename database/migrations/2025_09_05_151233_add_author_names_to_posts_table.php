<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // thêm sau excerpt cho dễ nhìn (đặt vị trí khác tuỳ bạn)
            $table->string('author_names', 255)->nullable()->after('excerpt');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('author_names');
        });
    }
};
