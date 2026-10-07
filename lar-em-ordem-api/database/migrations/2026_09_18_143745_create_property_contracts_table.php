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
        Schema::create('property_contracts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid("resident_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid("property_id")->constrained()->cascadeOnDelete();
            $table->date("start_date");
            $table->date("end_date")->nullable();
            $table->foreignId("resident_type_id")->constrained()->restrictOnDelete();
            $table->boolean('is_active')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_contracts');
    }
};
