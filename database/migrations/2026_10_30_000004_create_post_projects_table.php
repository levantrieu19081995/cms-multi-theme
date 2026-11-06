<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which project owns a post. A side table, so `posts` and the post-saving flow stay as they are. A post without a row
 * belongs to no project yet and is not offered by the multi-channel module. New posts get a row automatically
 * (ProjectPostAssigner); existing posts are assigned by the administrator with `php artisan cms:project-assign`.
 * No foreign key: a permanently deleted post may leave a harmless row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_projects', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id')->primary();
            $table->string('project_key', 100)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_projects');
    }
};
