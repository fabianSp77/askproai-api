<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated calls table with all fields from multiple migrations
     * Combines RetellAI integration, call metadata, relationships and analysis
     */
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            
            // RetellAI identifiers
            $table->string('call_id')->nullable()->index(); // RetellAI call ID
            $table->string('external_id')->nullable()->index(); // External system ID
            $table->string('conversation_id')->nullable()->index(); // RetellAI conversation ID
            
            // Call metadata
            $table->string('from_number', 20)->nullable();
            $table->string('to_number', 20)->nullable();
            $table->timestamp('start_timestamp')->nullable();
            $table->timestamp('end_timestamp')->nullable();
            $table->string('call_status')->nullable(); // 'ringing', 'in-progress', 'ended', 'failed'
            $table->boolean('call_successful')->nullable();
            $table->unsignedInteger('duration_sec')->nullable();
            $table->string('disconnect_reason')->nullable();
            
            // Cost tracking
            $table->unsignedInteger('cost_cents')->nullable(); // Cost in cents
            
            // Relationships
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->uuid('branch_id')->nullable()->index();
            $table->unsignedBigInteger('phone_number_id')->nullable()->index();
            $table->unsignedBigInteger('agent_id')->nullable()->index();
            
            // Call content and analysis
            $table->longText('transcript')->nullable();
            $table->json('analysis')->nullable(); // AI analysis results
            $table->json('details')->nullable(); // Additional call details
            $table->json('raw')->nullable(); // Raw webhook data
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index(['tenant_id', 'call_status']);
            $table->index(['tenant_id', 'call_successful']);
            $table->index(['tenant_id', 'start_timestamp']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'from_number']);
            $table->index(['tenant_id', 'to_number']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};