<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Security and API key management tables
     * Includes password resets and secure API key storage
     */
    public function up(): void
    {
        // Password Reset Tokens - For password recovery
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            
            // Index for cleanup
            $table->index('created_at');
        });

        // API Keys - Secure API key management
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('name'); // Human-readable name
            $table->string('key_hash')->unique(); // Hashed API key
            $table->string('key_prefix', 8); // First 8 chars for identification
            $table->json('permissions')->nullable(); // API permissions
            $table->json('rate_limits')->nullable(); // Rate limiting config
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index(['tenant_id', 'active']);
            $table->index(['tenant_id', 'user_id']);
            $table->index(['key_prefix', 'active']);
        });

        // Security Events - Log security-related events
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('event_type'); // 'login', 'logout', 'failed_login', 'api_key_used'
            $table->string('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('metadata')->nullable(); // Additional event data
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index(['tenant_id', 'event_type']);
            $table->index(['tenant_id', 'user_id']);
            $table->index(['event_type', 'created_at']);
            $table->index(['risk_level', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('password_reset_tokens');
    }
};