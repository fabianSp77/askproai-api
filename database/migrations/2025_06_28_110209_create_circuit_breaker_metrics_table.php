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
        if (!Schema::hasTable('circuit_breaker_metrics')) {
            Schema::create('circuit_breaker_metrics', function (Blueprint $table) {
                $table->id();
                $table->string('service', 50)->index();
                $table->enum('status', ['success', 'failure']);
                $table->enum('state', ['closed', 'open', 'half_open']);
                $table->decimal('duration_ms', 10, 2)->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
                
                // Indexes for performance
                $table->index(['service', 'created_at']);
                $table->index(['service', 'status', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circuit_breaker_metrics');
    }
};