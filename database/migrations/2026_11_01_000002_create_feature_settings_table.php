<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature Registry: trạng thái bật/tắt của từng chức năng cho bản triển khai hiện tại.
 * Định nghĩa chức năng (tên, route, quyền, icon) nằm trong code; bảng này chỉ lưu "đang bật hay tắt" và cấu hình riêng.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('feature_settings')) {
            return;
        }
        Schema::create('feature_settings', function (Blueprint $table) {
            $table->id();
            $table->string('feature_key', 100)->unique();
            $table->boolean('enabled')->default(true);
            $table->json('configuration')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Bảng do chính migration này tạo (chỉ chứa công tắc cấu hình, không có dữ liệu nghiệp vụ).
        Schema::dropIfExists('feature_settings');
    }
};
