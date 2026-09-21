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
            $table->id();
            $table->foreignId('assistance_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_provider_id')->constrained();
            $table->decimal('estimated_value', 12, 2);
            $table->text('description');
            $table->date('validity');                    
            $table->string('availability', 100);  
            $table->enum('status', ['accepted', 'pending', 'regected']);
            $table->dateTime('submission_date')->useCurrent();
            $table->dateTime('response_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
