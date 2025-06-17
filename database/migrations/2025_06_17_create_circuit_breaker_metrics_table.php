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
        Schema::create('circuit_breaker_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('service', 50)->index(); // calcom, retell, stripe
            $table->enum('status', ['success', 'failure']);
            $table->enum('state', ['closed', 'open', 'half_open']);
            $table->decimal('duration_ms', 10, 2)->nullable();
            $table->timestamp('created_at')->nullable();
            
            // Indexes for monitoring queries
            $table->index(['service', 'status', 'created_at']);
            $table->index(['service', 'state', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circuit_breaker_metrics');
    }
};