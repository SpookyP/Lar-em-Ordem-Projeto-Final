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
            $table->id();
            $table->foreignId("resident_id");
            $table->foreignId("property_id");
            $table->date("start_date")->nullable();
            $table->date("end_date")->nullable();
            $table->foreignId("resident_type_id");
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
