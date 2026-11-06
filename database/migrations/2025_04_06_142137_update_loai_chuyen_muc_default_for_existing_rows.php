<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('term_taxonomy')->whereNull('loai_chuyen_muc')->update(['loai_chuyen_muc' => 'cong_bo']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('term_taxonomy')->update(['loai_chuyen_muc' => null]);
    }
};
