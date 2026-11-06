<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('title', 255)->change();
            $table->text('content')->change();
            $table->text('answer')->nullable()->change(); // ✅ Cho phép null
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('title', 50)->change();
            $table->string('content', 255)->change();
            $table->string('answer', 255)->nullable()->change(); // ✅ giữ nguyên nullable
        });
    }
};
