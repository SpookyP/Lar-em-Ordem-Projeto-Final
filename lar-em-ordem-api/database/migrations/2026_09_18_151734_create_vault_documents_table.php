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
        Schema::create('vault_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignUlid('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('document_category_id')->constrained('document_categories')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->uuid('file_id')->index();

            $table->date('issue_date')->nullable();
            $table->date('expiration_date')->nullable();

            // Dados devolvidos pelo Script Python
            $table->json('extracted_data')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vault_documents');
    }
};
