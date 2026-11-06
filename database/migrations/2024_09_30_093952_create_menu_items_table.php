<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->integer('menu_id');
            $table->id();
            $table->string('title');
            $table->integer('parent_id')->default(0);
            $table->string('treepath');
            $table->string('url');
            $table->enum('type', ['url', 'category', 'tag', 'post', 'page', 'image', 'video', 'docs'])->default('url');
            $table->integer('ref_id');
            $table->enum('target', ['_blank', '_self', '_parent', '_top'])->default('_self');
            $table->integer('ordinal')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
