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
        Schema::create('consumption_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('consumption_id')->constrained()->onDelete('cascade');
  
            $table->string('name');                         // e.g. "Energia consumida", "Termo fixo", "IVA"
            $table->string('category')->nullable();         // Ex: 'consumption', 'fixed_fee', 'tax', 'discount'
            $table->decimal('amount', 10, 2)->nullable();   // Optional amount (e.g. 61 kWh)
            $table->string('unit')->nullable();             // Unit (e.g. "kWh", "kVA", "m³")
            $table->decimal('cost', 10, 2);                 // Cost of this item in euros
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumption_items');
    }
};
