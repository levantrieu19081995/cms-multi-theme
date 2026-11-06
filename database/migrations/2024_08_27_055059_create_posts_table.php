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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->longtext('content')->nullable();
            $table->string('slug')->nullable();
            $table->bigInteger('author_id')->nullable();
            $table->bigInteger('publisher_id')->nullable();
            $table->string('status')->nullable();
            $table->string('comment_status')->nullable();
            $table->string('post_type')->nullable();
            $table->text('excerpt')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('publication_date_expires')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
