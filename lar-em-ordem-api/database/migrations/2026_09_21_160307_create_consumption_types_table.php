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
        Schema::create('consumption_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: Eletricidade , Gás, Água
            $table->string('unit_of_measure'); // Ex: m2, m3, W
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumption_types');
    }
};
