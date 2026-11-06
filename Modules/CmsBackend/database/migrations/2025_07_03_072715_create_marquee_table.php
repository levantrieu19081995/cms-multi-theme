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
        Schema::create('marquee', function (Blueprint $table) {
            $table->id('id');
            $table->string('name', 50)->nullable(false);
            $table->string('content', 255)->nullable();
            $table->bigInteger('user_id');
            $table->integer('show_home_page')->nullable(false)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marquee');
    }
};