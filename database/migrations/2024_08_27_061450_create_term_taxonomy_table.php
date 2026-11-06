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
        Schema::create('term_taxonomy', function (Blueprint $table) {
            $table->id('term_taxonomy_id');
            $table->string('taxonomy')->nullable();
            $table->string('description')->nullable();
            $table->bigInteger('term_id')->nullable();
            $table->bigInteger('parent')->default('0');
            $table->bigInteger('count')->default('0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('term_taxonomy');
    }
};
