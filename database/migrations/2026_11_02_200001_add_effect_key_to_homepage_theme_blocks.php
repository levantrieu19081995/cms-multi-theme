<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Homepage Layout Manager: hiệu ứng Developer chọn cho từng cụm của một theme. NULL = hiệu ứng mặc định của theme (giữ nguyên HTML hiện tại);
 * `none`, `reveal`, `stagger` = giá trị trong allow-list của EffectCatalog (không lưu mã JS/CSS). Chỉ thêm một cột, không đụng dữ liệu.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('homepage_theme_blocks') && ! Schema::hasColumn('homepage_theme_blocks', 'effect_key')) {
            Schema::table('homepage_theme_blocks', function (Blueprint $table) {
                $table->string('effect_key', 40)->nullable()->after('variant_key');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('homepage_theme_blocks', 'effect_key')) {
            Schema::table('homepage_theme_blocks', function (Blueprint $table) {
                $table->dropColumn('effect_key');
            });
        }
    }
};
