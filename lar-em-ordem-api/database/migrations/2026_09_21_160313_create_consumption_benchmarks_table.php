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
            $table->foreignId('typology_id')->constrained('property_typologies')->cascadeOnDelete();
            $table->string('region', 100);
            $table->string('reference_period', 10);
            $table->date('period_start');
            $table->decimal('average_value', 10, 3);
            $table->unsignedInteger('sample_size');

            $table->unique([
                'consumption_type_id',
                'property_type_id',
                'typology_id',
                'region', 
                'reference_period', 
                'period_start',
            ], 'cb_dimensions_period_unique');
            
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
