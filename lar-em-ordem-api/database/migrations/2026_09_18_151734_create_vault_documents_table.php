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
            $table->ulid('id')->primary();
            $table->foreignUlid('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('document_category_id')->constrained('document_categories');

            $table->string('name', 150);
            $table->text('description')->nullable();

            $table->foreignUlid('file_id')->nullable()->constrained('files')->nullOnDelete();

            $table->string('file_hash')->nullable();

            $table->date('issue_date')->nullable();
            $table->date('expiration_date')->nullable();
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
