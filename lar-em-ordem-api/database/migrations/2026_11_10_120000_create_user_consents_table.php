<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consent history (RGPD art. 7): rows are only added, never changed, so the answer
     * at any point in time, and the policy version it referred to, can be shown.
     */
    public function up(): void
    {
        Schema::create('user_consents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 50);              // privacy_policy | data_sharing
            $table->boolean('granted');
            $table->string('policy_version', 20);
            $table->string('source', 30);            // signup | google | microsoft | settings | seed
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_consents');
    }
};