<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Liga prestador, categoria e especialidade (fixa ou custom).
     */
    public function up(): void
    {
        Schema::create('provider_specialties', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('service_provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('problem_category_id')->constrained();
            $table->foreignId('service_specialty_id')->nullable()->constrained();
            $table->string('custom_name')->nullable();

            $table->unique(['service_provider_id', 'service_specialty_id'], 'provider_specialties_fixed_unique');
            $table->unique(['service_provider_id', 'problem_category_id', 'custom_name'], 'provider_specialties_custom_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_specialties');
    }
};