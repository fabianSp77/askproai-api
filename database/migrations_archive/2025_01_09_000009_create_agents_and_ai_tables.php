<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated AI and agent tables for RetellAI integration
     * Includes agents, retell_agents, and retell_webhooks
     */
    public function up(): void
    {
        // Agents - Internal agent/user records
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('name');
            $table->string('retell_agent_id')->nullable(); // Link to RetellAI agent
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index(['tenant_id', 'active']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index('retell_agent_id');
        });

        // Retell Agents - RetellAI-specific agent configurations
        Schema::create('retell_agents', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->string('retell_id')->unique(); // RetellAI agent ID
            $table->string('name');
            $table->text('voice_config')->nullable(); // JSON voice configuration
            $table->text('llm_config')->nullable(); // JSON LLM configuration
            $table->text('response_engine_config')->nullable(); // JSON response config
            $table->string('language', 10)->default('en-US');
            $table->boolean('active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index(['tenant_id', 'active']);
            $table->index('retell_id');
        });

        // Retell Webhooks - Webhook event logging
        Schema::create('retell_webhooks', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable();
            $table->string('event_type')->index();
            $table->string('call_id')->nullable()->index();
            $table->string('conversation_id')->nullable()->index();
            $table->json('payload');
            $table->boolean('processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index(['event_type', 'processed']);
            $table->index(['call_id', 'event_type']);
            $table->index(['processed', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retell_webhooks');
        Schema::dropIfExists('retell_agents');
        Schema::dropIfExists('agents');
    }
};