<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Translation tables for the multilingual CMS (plan: Phase 2). The original rows stay in their own tables; each
 * translation row hangs off the original object by id, so ids, relations, workflow and status are shared by all locales.
 * The older generic `translations` table is intentionally left untouched and unused.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->string('locale', 8);
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();

            $table->unique(['post_id', 'locale']);
            $table->unique(['locale', 'slug']);
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });

        Schema::create('term_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('term_id');
            $table->string('locale', 8);
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();

            $table->unique(['term_id', 'locale']);
            $table->unique(['locale', 'slug']);
            $table->foreign('term_id')->references('term_id')->on('terms')->cascadeOnDelete();
        });

        Schema::create('menu_item_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_item_id');
            $table->string('locale', 8);
            $table->string('title')->nullable();
            $table->string('custom_url')->nullable();
            $table->timestamps();

            $table->unique(['menu_item_id', 'locale']);
            $table->foreign('menu_item_id')->references('id')->on('menu_items')->cascadeOnDelete();
        });

        // Keyed by options.key (not unique in `options`, so no foreign key): homepage texts, unit information, labels.
        Schema::create('option_translations', function (Blueprint $table) {
            $table->id();
            $table->string('option_key');
            $table->string('locale', 8);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['option_key', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_translations');
        Schema::dropIfExists('menu_item_translations');
        Schema::dropIfExists('term_translations');
        Schema::dropIfExists('post_translations');
    }
};
