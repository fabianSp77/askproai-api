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
        Schema::create('service_synonyms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')
                ->constrained('services')
                ->onDelete('cascade');
            $table->string('synonym');
            $table->decimal('confidence', 3, 2)->default(1.00); // 0.00 to 1.00
            $table->timestamps();

            // Ensure unique synonyms per service
            $table->unique(['service_id', 'synonym']);

            // Index for fast lookup
            $table->index('synonym');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_synonyms');
    }
};
