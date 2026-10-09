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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number');
            $table->date('issue_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_amount', 10, 2);
            $table->string('supplier');
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->unique([
                'user_id',
                'supplier',
                'invoice_number',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
