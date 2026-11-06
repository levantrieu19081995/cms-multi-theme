<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ks_khao_sat_users', function (Blueprint $table) {
            $table->string('school_key', 100)->nullable()->after('ksu_so_dien_thoai');
            $table->string('school_name', 255)->nullable()->after('school_key');
        });
    }

    public function down(): void
    {
        Schema::table('ks_khao_sat_users', function (Blueprint $table) {
            $table->dropColumn(['school_key', 'school_name']);
        });
    }
};
