<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One preparation of a multi-channel publication. title / message / image_url / public_url are a snapshot taken when the
 * request is created: history never reads them back from the CMS post. post_id has no foreign key on purpose, so the history
 * survives when the post is permanently deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_publications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->nullable()->index();
            $table->string('project_key', 100);
            $table->string('locale', 10);
            $table->string('title');
            $table->text('message');
            $table->text('image_url')->nullable();
            $table->text('public_url');
            $table->string('status', 30)->default('draft');
            // Same value for the same form: a repeated submit returns the first publication instead of creating a second one.
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();

            $table->index(['project_key', 'status'], 'social_publications_project_status_index');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_publications');
    }
};
