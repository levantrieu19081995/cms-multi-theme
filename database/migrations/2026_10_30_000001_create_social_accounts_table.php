<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts of the social channels a deployment publishes to (Phase 2 fills it; Phase 1 only reads it).
 * `credentials` holds the encrypted token payload (encrypted cast on the model). Nothing is stored in Phase 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('project_key', 100);
            $table->string('platform', 30);
            $table->string('name');
            $table->string('external_account_id')->nullable();
            $table->string('status', 30)->default('disconnected');
            $table->longText('credentials')->nullable();
            $table->dateTime('token_expires_at')->nullable();
            $table->dateTime('last_checked_at')->nullable();
            $table->unsignedBigInteger('connected_by')->nullable();
            $table->timestamps();

            $table->index(['project_key', 'platform', 'status'], 'social_accounts_project_platform_status_index');
            $table->unique(['project_key', 'platform', 'external_account_id'], 'social_accounts_project_platform_external_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
