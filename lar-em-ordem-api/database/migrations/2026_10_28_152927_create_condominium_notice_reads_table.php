<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condominium_notice_reads', function (Blueprint $table) {
            $table->foreignUlid('condominium_notice_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('resident_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->primary(['condominium_notice_id', 'resident_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condominium_notice_reads');
    }
};