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
        if (!Schema::hasTable('qlvb_nhom_van_ban')) {
            Schema::create('qlvb_nhom_van_ban', function (Blueprint $table) {
                $table->id('nvb_id');
                $table->string('nvb_name', 50);
                $table->string('nvb_description', 255);
                $table->string('nvb_slug', 50);
                $table->bigInteger('user_id');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qlvb_nhom_van_ban');
    }
};
