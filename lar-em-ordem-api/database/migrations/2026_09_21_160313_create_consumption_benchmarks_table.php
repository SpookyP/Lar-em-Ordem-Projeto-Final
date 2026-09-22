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
        Schema::create('consumption_benchmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumption_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('typology_id')->constrained()->cascadeOnDelete();
            $table->decimal('area', 8, 2)->nullable();
            $table->string('reference_period'); // Ex: Mensal ou Annual
            $table->decimal('average_value', 10, 3);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumption_benchmarks');
    }
};
