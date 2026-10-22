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
        Schema::create('proposals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('assistance_request_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('service_provider_id')->constrained();
            $table->decimal('estimated_value', 12, 2);
            $table->text('description');
            $table->date('validity');                    
            $table->date('available_date');
            $table->time('available_from');
            $table->time('available_until');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'withdrawn'])->default('pending');
            $table->dateTime('response_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['assistance_request_id', 'service_provider_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
