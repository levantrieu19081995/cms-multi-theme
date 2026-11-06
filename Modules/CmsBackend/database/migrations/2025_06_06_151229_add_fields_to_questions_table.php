<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm cột vào bảng questions.
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->string('title');
            $table->string('content');
            $table->bigInteger('user_id');
            $table->string('answer')->nullable();
            $table->bigInteger('answered_by')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Xóa các cột đã thêm khi rollback.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};