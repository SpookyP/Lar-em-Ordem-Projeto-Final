<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condominium_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('condominium_id')->constrained()->cascadeOnDelete();
            $table->foreignId('condominium_document_category_id')
                ->constrained(indexName: 'condo_documents_category_id_foreign');
            $table->foreignUlid('file_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('document_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condominium_documents');
    }
};