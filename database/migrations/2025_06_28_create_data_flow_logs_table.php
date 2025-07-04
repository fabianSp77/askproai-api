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
        Schema::create('data_flow_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('correlation_id')->unique()->index();
            $table->uuid('parent_correlation_id')->nullable()->index();
            $table->string('type', 50)->index(); // webhook_incoming, api_outgoing, etc.
            $table->string('source', 50)->index(); // retell, calcom, stripe, etc.
            $table->string('destination', 50)->index();
            $table->string('status', 20)->default('started')->index(); // started, completed, failed
            $table->json('metadata')->nullable();
            $table->json('steps')->nullable();
            $table->json('statistics')->nullable();
            $table->json('summary')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('duration_ms', 10, 2)->nullable()->index();
            $table->text('sequence_diagram')->nullable();
            $table->timestamps();
            
            // Composite indexes for common queries
            $table->index(['source', 'destination', 'created_at']);
            $table->index(['type', 'status', 'created_at']);
            $table->index(['correlation_id', 'status']);
        });
        
        // Create MCP logs table if we're logging to database
        Schema::create('mcp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service');
            $table->string('type', 50); // discovery, execution, error, etc.
            $table->text('task');
            $table->uuid('correlation_id')->index();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->index();
            
            $table->index(['service', 'type', 'created_at']);
            $table->index(['correlation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mcp_logs');
        Schema::dropIfExists('data_flow_logs');
    }
};