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
        Schema::create('posts_banners', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('post_id')->nullable();
            $table->bigInteger('ref_id')->nullable();
            $table->longtext('description')->nullable();
            $table->string('btn_text')->nullable();
            $table->tinyInteger('redirect')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
};
