<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condominium_notices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('condominium_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->timestamp('published_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condominium_notices');
    }
};