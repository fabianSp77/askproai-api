<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create MCP API Keys table for secure authentication
 * 
 * This migration creates the table for managing MCP API keys with:
 * - Company and reseller association
 * - Permission management
 * - IP restrictions
 * - Rate limiting
 * - Expiration handling
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mcp_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->onDelete('cascade')
                ->comment('Associated company');
            
            $table->foreignId('reseller_id')
                ->nullable()
                ->constrained('companies')
                ->onDelete('cascade')
                ->comment('Associated reseller company');
            
            $table->string('name')
                ->comment('Human-readable name for the API key');
            
            $table->string('key', 40)
                ->unique()
                ->comment('Unique API key (mcp_[32 chars])');
            
            $table->json('permissions')
                ->nullable()
                ->comment('Array of permissions granted to this key');
            
            $table->json('allowed_ips')
                ->nullable()
                ->comment('Array of allowed IP addresses (null = no restrictions)');
            
            $table->integer('rate_limit')
                ->default(1000)
                ->comment('Requests per minute limit');
            
            $table->boolean('is_active')
                ->default(true)
                ->comment('Whether the key is active');
            
            $table->timestamp('expires_at')
                ->nullable()
                ->comment('When the key expires (null = never)');
            
            $table->timestamp('last_used_at')
                ->nullable()
                ->comment('When the key was last used');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['company_id', 'is_active']);
            $table->index(['reseller_id', 'is_active']);
            $table->index(['is_active', 'expires_at']);
            $table->index('last_used_at');
        });
        
        // Add comment to table
        DB::statement("ALTER TABLE mcp_api_keys COMMENT = 'API keys for MCP authentication and authorization'");
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mcp_api_keys');
    }
};
