<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One delivery per channel of a publication. A publication holds each channel at most once. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_publication_channels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('social_publication_id');
            $table->string('channel', 30);
            $table->unsignedBigInteger('social_account_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('remote_post_id')->nullable();
            $table->text('remote_url')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->dateTime('published_at')->nullable();
            $table->dateTime('removed_at')->nullable();
            $table->dateTime('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['social_publication_id', 'channel'], 'social_publication_channels_publication_channel_unique');
            $table->index(['channel', 'status'], 'social_publication_channels_channel_status_index');
            $table->index('social_account_id');
            $table->foreign('social_publication_id', 'social_publication_channels_publication_foreign')
                ->references('id')->on('social_publications')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_publication_channels');
    }
};
