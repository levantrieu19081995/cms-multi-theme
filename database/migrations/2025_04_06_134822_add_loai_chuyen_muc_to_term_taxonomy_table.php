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
        Schema::table('term_taxonomy', function (Blueprint $table) {
            $table->string('loai_chuyen_muc')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('term_taxonomy', function (Blueprint $table) {
            $table->dropColumn('loai_chuyen_muc');
        });
    }
};
