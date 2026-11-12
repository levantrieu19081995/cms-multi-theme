<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Interaction figures read back from the platform for a published delivery (for Facebook: reactions, comments, shares).
 * Nullable: a delivery that was never synchronized has none. The time of the last reading stays in `last_synced_at`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_publication_channels', function (Blueprint $table) {
            $table->json('metrics')->nullable()->after('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('social_publication_channels', function (Blueprint $table) {
            $table->dropColumn('metrics');
        });
    }
};
