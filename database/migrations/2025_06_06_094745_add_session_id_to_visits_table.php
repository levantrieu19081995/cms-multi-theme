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
        // Schema::table('visits', function (Blueprint $table) {
        //     $table->string('session_id')->nullable()->after('primary_key');
        // });
        if (!Schema::hasColumn('visits', 'session_id')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->string('session_id')->nullable()->after('primary_key');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn('session_id');
        });
    }
};
