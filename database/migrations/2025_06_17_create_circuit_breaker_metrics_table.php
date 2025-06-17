<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('circuit_breaker_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('service', 50)->index();
            $table->enum('status', ['success', 'failure'])->index();
            $table->enum('state', ['closed', 'open', 'half_open'])->index();
            $table->decimal('duration_ms', 10, 2)->nullable();
            $table->timestamp('created_at')->index();
            
            // Composite index for performance
            $table->index(['service', 'created_at']);
            $table->index(['service', 'status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('circuit_breaker_metrics');
    }
};