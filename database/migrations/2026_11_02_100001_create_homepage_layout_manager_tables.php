<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Homepage Layout Manager: instance của cụm, vị trí cụm theo theme và cấu hình nguồn nội dung theo theme.
 * Definition/renderer/effect nằm trong code; các bảng này không chứa class, Blade path, JavaScript hay CSS selector.
 * Chỉ tạo bảng mới, không đụng bảng nghiệp vụ.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('homepage_block_instances')) {
            Schema::create('homepage_block_instances', function (Blueprint $table) {
                $table->id();
                $table->string('instance_key', 100)->unique();
                $table->string('definition_key', 100)->index();
                $table->string('name_vi', 255);
                $table->string('name_en', 255)->nullable();
                $table->boolean('is_system')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('homepage_theme_blocks')) {
            Schema::create('homepage_theme_blocks', function (Blueprint $table) {
                $table->id();
                $table->string('theme_key', 60);
                $table->foreignId('block_instance_id')->constrained('homepage_block_instances')->cascadeOnDelete();
                $table->string('zone_key', 60);
                $table->string('variant_key', 60)->nullable();
                $table->boolean('enabled')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['theme_key', 'block_instance_id'], 'homepage_theme_blocks_unique');
                $table->index(['theme_key', 'zone_key', 'enabled', 'sort_order'], 'homepage_theme_blocks_order');
            });
        }

        if (! Schema::hasTable('homepage_block_settings')) {
            Schema::create('homepage_block_settings', function (Blueprint $table) {
                $table->id();
                $table->string('theme_key', 60);
                $table->foreignId('block_instance_id')->constrained('homepage_block_instances')->cascadeOnDelete();
                $table->json('settings')->nullable();
                $table->timestamps();

                $table->unique(['theme_key', 'block_instance_id'], 'homepage_block_settings_unique');
            });
        }
    }

    public function down(): void
    {
        // Chỉ xóa 3 bảng do migration này tạo (cấu hình bố cục, không chứa dữ liệu nghiệp vụ). Chế độ legacy vẫn chạy bằng option cũ.
        Schema::dropIfExists('homepage_block_settings');
        Schema::dropIfExists('homepage_theme_blocks');
        Schema::dropIfExists('homepage_block_instances');
    }
};
