<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('doi_tac', function (Blueprint $table) {
            $table->id('id');
            $table->string('title',4000);
            $table->longText('content')->nullable();
            $table->string('slug',5000);
            $table->string('excerpt',5000)->nullable();
            $table->timestamps(); // includes created_at and updated_at
            $table->softDeletes(); // includes deleted_at
            $table->integer('show_home_page')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doi_tac');
    }
};