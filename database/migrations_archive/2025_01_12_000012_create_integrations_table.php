<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated integrations table with proper foreign keys
     * Includes external system integrations and credentials
     */
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('system'); // 'calcom', 'retell', 'stripe', etc.
            $table->string('name')->nullable(); // Human-readable name
            $table->json('credentials')->nullable(); // API keys, tokens, etc.
            $table->json('settings')->nullable(); // Additional configuration
            $table->boolean('active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index(['tenant_id', 'system']);
            $table->index(['tenant_id', 'active']);
            $table->index(['tenant_id', 'customer_id']);
            
            // Unique constraint for one integration per system per customer
            $table->unique(['tenant_id', 'customer_id', 'system']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};