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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId("property_type_id")->constrained()->restrictOnDelete();
            $table->foreignId("property_typology_id")->constrained()->restrictOnDelete();
            $table->foreignId("address_id")->constrained()->cascadeOnDelete();
            $table->foreignId("condominium_id")->constrained()->restrictOnDelete()->nullable();
            $table->integer("area");
            $table->string("fraction")->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
