<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The pictures / video of a publication, in order, as a snapshot like the rest of its content: a list of addresses
 * (their kind, image or video, is read from the file extension). `image_url` stays the first picture, so existing screens and
 * rows keep working; a row without `media` is read as "its image_url, if any".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('social_publications', 'media')) {
            return;
        }

        Schema::table('social_publications', function (Blueprint $table) {
            $table->json('media')->nullable()->after('image_url');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('social_publications', 'media')) {
            Schema::table('social_publications', function (Blueprint $table) {
                $table->dropColumn('media');
            });
        }
    }
};
